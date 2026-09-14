<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\AnnexStock;
use App\Models\Bank;
use App\Models\BranchStock;
use App\Models\DpbvCollection;
use App\Models\PosMachine;
use App\Models\Guest;
use App\Models\HeadquartersStock;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\KdRegistrationCredit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServiceCenterStock;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    private function resolveServiceCenterByCode(?string $code): ?User
    {
        $code = trim((string) $code);
        if ($code === '') {
            return null;
        }

        $variants = array_values(array_unique(array_filter([
            $code,
            strtoupper($code),
            strtolower($code),
        ], fn ($v) => $v !== '')));

        return User::whereIn('service_center_code', $variants)
            ->whereHas('role', function ($q) {
                $q->where('name', Role::SERVICE_CENTER);
            })
            ->first();
    }

    /**
     * @return array{posMachines: \Illuminate\Support\Collection, banks: \Illuminate\Support\Collection}
     */
    private function checkoutPaymentOptions(?User $user): array
    {
        $posMachines = PosMachine::query()
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        $banksQuery = Bank::where('is_active', true);
        $hqId = null;
        if ($user) {
            $user->loadMissing('role');
            $forBanks = $user->bankContextUser();
            if ($forBanks->role?->name === 'headquarters') {
                $hqId = (int) $forBanks->id;
            } elseif ($forBanks->role?->name === 'branch') {
                $hqId = (int) $forBanks->created_by_user_id;
            } elseif ($forBanks->role?->name === 'service_center' && $forBanks->created_by_user_id) {
                $branch = User::find($forBanks->created_by_user_id);
                $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
            } elseif (in_array($forBanks->role?->name, ['annex', 'dispatch', 'accountant'], true) && $forBanks->created_by_user_id) {
                $creator = User::with('role')->find($forBanks->created_by_user_id);
                if ($creator && $creator->role?->name === 'service_center' && $creator->created_by_user_id) {
                    $branch = User::find($creator->created_by_user_id);
                    $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
                } elseif ($creator && $creator->role?->name === 'branch') {
                    $hqId = (int) $creator->created_by_user_id;
                }
            }
        }
        if ($hqId) {
            $banksQuery->where('headquarters_user_id', $hqId);
        }

        return [
            'posMachines' => $posMachines,
            'banks' => $banksQuery->orderBy('name')->get(),
        ];
    }

    private function effectiveDpbvQuery(User $user)
    {
        $query = DpbvCollection::query()->where('user_id', $user->id);

        $serviceCenterCode = trim((string) ($user->service_center_code ?? ''));
        if (($user->role?->name ?? null) === 'service_center' && $serviceCenterCode !== '') {
            $scVariants = array_values(array_unique([
                $serviceCenterCode,
                strtoupper($serviceCenterCode),
                strtolower($serviceCenterCode),
            ]));

            $query->orWhere(function ($q) use ($scVariants) {
                $q->whereNull('user_id')->whereIn('sc', $scVariants);
            });
        }

        return $query;
    }

    private function getCartData(Request $request): array
    {
        $cart = $request->session()->get('cart', []);
        $cartItems = [];
        $cartSubtotal = 0;
        $cartBv = 0;
        $cartPv = 0;
        $user = $request->user();

        foreach ($cart as $itemCode => $qty) {
            $product = Product::with('category')->where('item_code', $itemCode)->where('is_active', true)->first();
            if ($product && $qty > 0) {
                $unitPrice = $product->getPriceForUser($user);
                $lineTotal = $unitPrice * $qty;
                $cartItems[] = (object) [
                    'product' => $product,
                    'quantity' => (int) $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'line_bv' => $product->bv * $qty,
                    'line_pv' => $product->pv * $qty,
                ];
                $cartSubtotal += $lineTotal;
                $cartBv += $product->bv * $qty;
                $cartPv += $product->pv * $qty;
            }
        }

        $coupon = null;
        $discountAmount = 0;
        $couponCode = $request->session()->get('coupon_code');
        if ($couponCode) {
            $coupon = \App\Models\Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid()) {
                $discountAmount = ($coupon->discount_percentage / 100) * $cartSubtotal;
            } else {
                $request->session()->forget('coupon_code');
            }
        }

        $cartTotal = max(0, $cartSubtotal - $discountAmount);
        $cartCount = array_sum($cart);

        return compact('cartItems', 'cartSubtotal', 'cartBv', 'cartPv', 'cartCount', 'coupon', 'discountAmount', 'cartTotal');
    }

    public function show(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('message', 'Your cart is empty. Add items to checkout.');
        }

        $user = $request->user();
        $user?->load(['role', 'createdBy.role']);
        $kdId = trim((string) $request->session()->get('kd_id', ''));
        $customerName = trim((string) $request->session()->get('customer_name', ''));
        // Guests can checkout without KD NO/Customer Name; orders stored with null, can be updated later
        // Cashier → parent wallet; Distributor → own wallet
        $walletOwner = $user ? $user->walletOwnerForShopping() : null;

        $walletBalance = $walletOwner ? (float) ($walletOwner->wallet_balance ?? 0) : 0;
        $canPayWithWallet = $walletBalance >= $data['cartTotal'];

        // Calculate DPBV balance and naira equivalent
        $totalDpbv = $user ? (float) $this->effectiveDpbvQuery($user)->sum('dpbv') : 0;
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
        // Use rounded comparison to avoid float precision blocking DPBV option.
        $dpbvProductsAllowed = true;
        foreach ($data['cartItems'] as $item) {
            if (! ($item->product->can_use_dpbv ?? true)) {
                $dpbvProductsAllowed = false;
                break;
            }
        }

        // Enable the DPBV radio based on balance ONLY.
        // The real product eligibility check is enforced again during `placeOrder`.
        $canPayWithDpbv = round($dpbvNairaEquivalent, 2) >= round((float) $data['cartTotal'], 2);

        // Check KD Registration credit balance if KD NO is provided
        $kdCreditBalance = 0;
        $canPayWithCredit = false;
        if ($kdId) {
            $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
            if ($kdRegistration) {
                $kdCreditBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                $canPayWithCredit = $kdCreditBalance >= $data['cartTotal'];
            }
        }

        $paymentOptions = $this->checkoutPaymentOptions($user);

        return view('checkout.show', array_merge($data, [
            'walletBalance' => $walletBalance,
            'canPayWithWallet' => $canPayWithWallet,
            'totalDpbv' => $totalDpbv,
            'dpbvNairaEquivalent' => $dpbvNairaEquivalent,
            'canPayWithDpbv' => $canPayWithDpbv,
            'dpbvProductsAllowed' => $dpbvProductsAllowed,
            'kdId' => $kdId,
            'customerName' => $customerName,
            'kdCreditBalance' => $kdCreditBalance,
            'canPayWithCredit' => $canPayWithCredit,
            'posMachines' => $paymentOptions['posMachines'],
            'banks' => $paymentOptions['banks'],
            'collectionBranch' => $request->session()->get('collection_branch_id')
                ? User::with('role')->find($request->session()->get('collection_branch_id'))
                : null,
        ]));
    }

    public function checkKdCredit(Request $request)
    {
        $request->validate([
            'kd_no' => 'required|string|max:100',
        ]);

        $kdNo = trim($request->input('kd_no'));
        $kdRegistration = KdRegistration::where('kd_no', $kdNo)->first();

        if (! $kdRegistration) {
            return response()->json([
                'has_credit' => false,
                'balance' => 0,
                'can_pay' => false,
            ]);
        }

        $balance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));

        // Get cart subtotal to check if balance is sufficient
        $cart = $request->session()->get('cart', []);
        $cartSubtotal = 0;
        $user = $request->user();

        foreach ($cart as $itemCode => $qty) {
            $product = Product::where('item_code', $itemCode)->where('is_active', true)->first();
            if ($product && $qty > 0) {
                $unitPrice = $product->getPriceForUser($user);
                $cartSubtotal += $unitPrice * $qty;
            }
        }

        return response()->json([
            'has_credit' => true,
            'balance' => (float) $balance,
            'can_pay' => $balance >= $cartSubtotal,
        ]);
    }

    public function placeOrder(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $deliveryType = $request->input('delivery_type', 'ship');
        $splitPayment = $request->boolean('split_payment');
        $rules = [
            'payment_method' => 'nullable|in:wallet,pay_on_delivery,dpbv,kd_credit,split',
            'split_payment' => 'nullable',
            'pos_amount_paid' => 'nullable|numeric|min:0',
            'bank_amount_paid' => 'nullable|numeric|min:0',
            'pos_machine_id' => 'nullable|integer',
            'bank_account_id' => 'nullable|integer',
            'split_wallet_amount' => 'nullable|numeric|min:0',
            'split_kd_credit_amount' => 'nullable|numeric|min:0',
            'split_cash_amount' => 'nullable|numeric|min:0',
            'split_cheque_amount' => 'nullable|numeric|min:0',
            'split_dpbv_amount' => 'nullable|numeric|min:0',
            'kd_id' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
            'delivery_type' => 'required|in:walk_in,ship',
            'notes' => 'nullable|string|max:1000',
        ];
        if ($deliveryType === 'ship') {
            $rules['shipping_address'] = 'required|string|max:500';
            $rules['shipping_city'] = 'required|string|max:100';
            $rules['shipping_phone'] = 'required|string|max:50';
        }
        $rules['shipping_state'] = 'nullable|string|max:100';
        $rules['shipping_postal_code'] = 'nullable|string|max:20';
        $rules['sc_referral_code'] = 'nullable|string|max:100';
        $rules['collection_branch_id'] = 'nullable|integer';
        $rules['sc_collection_code'] = 'nullable|string|max:100';

        $request->validate($rules);

        $user = $request->user();
        $user?->load(['role', 'createdBy.role']);
        $paymentMethod = $splitPayment ? 'split' : ($request->input('payment_method') ?: Order::PAYMENT_PAY_ON_DELIVERY);
        $paymentBreakdown = null;
        $walletAmt = 0.0;
        $kdAmt = 0.0;
        $dpbvAmt = 0.0;
        $kdId = trim((string) $request->input('kd_id', ''));
        $customerName = trim((string) $request->input('customer_name', ''));
        $scReferralCode = trim((string) $request->input('sc_referral_code', ''));
        $collectionBranchId = (int) ($request->input('collection_branch_id') ?: $request->session()->get('collection_branch_id', 0));
        $collectionBranch = $collectionBranchId
            ? User::where('id', $collectionBranchId)->whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))->first()
            : null;
        if ($collectionBranchId && ! $collectionBranch) {
            return back()->withErrors(['collection_branch_id' => 'Choose a valid collection branch.'])->withInput();
        }

        // Cashier → parent wallet; Distributor → own wallet
        $walletOwner = $user->walletOwnerForShopping();

        // Distributor: resolve Service Center Code for Collection.
        // Stock source for distributor orders must come from this service center.
        // Wallet transfer (debit distributor, credit service center) remains wallet-only.
        $serviceCenterForDistributor = null;
        if ($user->role?->name === 'distributor') {
            $scCode = trim((string) $request->input('sc_collection_code', ''));
            if ($scCode === '') {
                return back()->withErrors(['sc_collection_code' => 'Service Center code for collection is required for distributor orders.'])->withInput();
            }

            $serviceCenterForDistributor = User::where('service_center_code', $scCode)
                ->whereHas('role', function ($q) {
                    $q->where('name', Role::SERVICE_CENTER);
                })
                ->first();

            if (! $serviceCenterForDistributor) {
                return back()->withErrors(['sc_collection_code' => 'Invalid Service Center code.'])->withInput();
            }
        }

        if ($splitPayment) {
            $walletAmt = round((float) $request->input('split_wallet_amount', 0), 2);
            $kdAmt = round((float) $request->input('split_kd_credit_amount', 0), 2);
            $cashAmt = round((float) $request->input('split_cash_amount', 0), 2);
            $chequeAmt = round((float) $request->input('split_cheque_amount', 0), 2);
            $posAmt = round((float) $request->input('pos_amount_paid', 0), 2);
            $bankAmt = round((float) $request->input('bank_amount_paid', 0), 2);
            $dpbvAmt = round((float) $request->input('split_dpbv_amount', 0), 2);
            $sum = round($walletAmt + $kdAmt + $cashAmt + $chequeAmt + $posAmt + $bankAmt + $dpbvAmt, 2);

            if ($sum <= 0) {
                return back()->withErrors(['split_payment' => 'Enter at least one payment amount.'])->withInput();
            }
            if ($sum !== round((float) $data['cartTotal'], 2)) {
                return back()->withErrors([
                    'split_payment' => 'Payment amounts must add up to the order total (₦'.number_format((float) $data['cartTotal'], 2).').',
                ])->withInput();
            }

            $walletBalance = $walletOwner ? (float) ($walletOwner->wallet_balance ?? 0) : 0;
            if ($walletAmt > 0 && $walletBalance < $walletAmt) {
                return back()->withErrors(['split_wallet_amount' => 'Insufficient wallet balance for the wallet amount entered.'])->withInput();
            }

            if ($kdAmt > 0) {
                if ($kdId === '') {
                    return back()->withErrors(['kd_id' => 'KD NO is required to pay any amount from KD Credit.'])->withInput();
                }
                $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
                if (! $kdRegistration) {
                    return back()->with('error', 'KD Registration not found.');
                }
                $kdCreditBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                if ($kdCreditBalance < $kdAmt) {
                    return back()->withErrors(['split_kd_credit_amount' => 'Insufficient KD Credit balance for the credit amount entered.'])->withInput();
                }
            }

            if ($dpbvAmt > 0) {
                $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
                $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
                if (round($dpbvNairaEquivalent, 2) < $dpbvAmt) {
                    return back()->withErrors(['split_dpbv_amount' => 'Insufficient DPBV balance. You have ₦'.number_format($dpbvNairaEquivalent, 2).' available.'])->withInput();
                }
                $productsNotAllowed = [];
                foreach ($data['cartItems'] as $item) {
                    if (! ($item->product->can_use_dpbv ?? true)) {
                        $productsNotAllowed[] = $item->product->name;
                    }
                }
                if ($productsNotAllowed !== []) {
                    return back()->with('error', 'The following products cannot be purchased with DPBV: '.implode(', ', $productsNotAllowed).'.');
                }
            }

            $posMachine = $request->filled('pos_machine_id')
                ? PosMachine::find($request->input('pos_machine_id'))
                : null;
            $bankAccount = $request->filled('bank_account_id')
                ? Bank::find($request->input('bank_account_id'))
                : null;

            $paymentBreakdown = [
                'wallet' => $walletAmt,
                'kd_credit' => $kdAmt,
                'dpbv' => $dpbvAmt,
                'cash' => $cashAmt,
                'cheque' => $chequeAmt,
                'pos' => $posAmt,
                'bank' => $bankAmt,
                'pos_machine' => $posMachine ? trim(($posMachine->bank_name ?: 'POS').($posMachine->account_number ? ' • '.$posMachine->account_number : '')) : null,
                'bank_account' => $bankAccount ? trim(($bankAccount->name ?: 'Bank').($bankAccount->account_number ? ' • '.$bankAccount->account_number : '')) : null,
                'total' => round((float) $data['cartTotal'], 2),
            ];
        } elseif ($paymentMethod === 'wallet') {
            $walletBalance = $walletOwner ? (float) ($walletOwner->wallet_balance ?? 0) : 0;
            if ($walletBalance < $data['cartTotal']) {
                return back()->with('error', 'Insufficient wallet balance.');
            }
        }

        // Check KD Registration credit balance if paying with credit
        if ($paymentMethod === 'kd_credit') {
            if (! $kdId) {
                return back()->with('error', 'KD NO is required to pay with credit.');
            }
            $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
            if (! $kdRegistration) {
                return back()->with('error', 'KD Registration not found.');
            }
            $kdCreditBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
            if ($kdCreditBalance < $data['cartTotal']) {
                return back()->with('error', 'Insufficient KD credit balance. You have ₦'.number_format($kdCreditBalance, 2).' available.');
            }
        }

        // DPBV checks (balance + product eligibility) when paying with DPBV
        if ($paymentMethod === 'dpbv') {
            $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
            $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
            if (round($dpbvNairaEquivalent, 2) < round((float) $data['cartTotal'], 2)) {
                return back()->with('error', 'Insufficient DPBV balance. You have ₦'.number_format($dpbvNairaEquivalent, 2).' available.');
            }

            $productsNotAllowed = [];
            foreach ($data['cartItems'] as $item) {
                if (! ($item->product->can_use_dpbv ?? true)) {
                    $productsNotAllowed[] = $item->product->name;
                }
            }
            if (! empty($productsNotAllowed)) {
                return back()->with('error', 'The following products cannot be purchased with DPBV: '.implode(', ', $productsNotAllowed).'. Please remove them from your cart or use a different payment method.');
            }
        }

        // Guest orders can have null kd_id/customer_name; save to kd_customers when provided
        if ($kdId !== '' && $customerName !== '') {
            $request->session()->put('kd_id', $kdId);
            $request->session()->put('customer_name', $customerName);
        }

        // Determine which user's stock to use (HQ, Branch, Service Center, or Annex)
        $stockOwner = $user;
        $roleName = $user->role?->name ?? '';

        // If cashier, they sell on behalf of the account that created them (HQ/Branch/SC/Annex)
        if (in_array($roleName, ['cashier', 'distributor'], true) && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        // Distributor override: use the entered Service Center as stock source.
        if ($serviceCenterForDistributor) {
            $stockOwner = $serviceCenterForDistributor;
            $roleName = Role::SERVICE_CENTER;
        }

        $stockUserId = null; // user whose stock we use (Branch, SC, or Annex)
        $isHeadquarters = $roleName === 'headquarters';
        if (in_array($roleName, ['branch', 'service_center', 'annex'], true)) {
            $stockUserId = (int) $stockOwner->id;
        }
        $branchUserId = $stockUserId; // keep for order.branch_user_id (used when deducting)

        // Check stock availability before placing order (using stock owner's account)
        foreach ($data['cartItems'] as $item) {
            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($stockOwner->id, $item->product->id);
                if ($avail < $item->quantity) {
                    return back()->with('error', "Insufficient Headquarters stock for {$item->product->name}. Available: {$avail}.");
                }
            } elseif ($stockUserId) {
                $avail = $this->getStockForUser($stockUserId, $roleName, $item->product->id);
                if ($avail < $item->quantity) {
                    return back()->with('error', "Insufficient stock for {$item->product->name}. Available: {$avail}.");
                }
            } else {
                // For regular users, check main product stock
                if ($item->product->stock < $item->quantity) {
                    return back()->with('error', "Insufficient stock for {$item->product->name}. Available: {$item->product->stock}.");
                }
            }
        }

        $shippingAddress = $deliveryType === 'walk_in' ? 'Walk-in (Pick up)' : $request->input('shipping_address');
        $shippingCity = $deliveryType === 'walk_in' ? '' : $request->input('shipping_city');
        $shippingState = $deliveryType === 'walk_in' ? '' : $request->input('shipping_state');
        $shippingPostal = $deliveryType === 'walk_in' ? '' : $request->input('shipping_postal_code');
        $shippingPhone = $deliveryType === 'walk_in' ? ($request->input('shipping_phone') ?: auth()->user()->phone ?? '') : $request->input('shipping_phone');

        $orderKdId = $kdId !== '' ? $kdId : null;
        $orderCustomerName = $customerName !== '' ? $customerName : null;
        if ($orderKdId && $orderCustomerName) {
            \App\Models\KdCustomer::firstOrCreate(
                ['kd_no' => $orderKdId],
                ['customer_name' => $orderCustomerName, 'user_id' => $user->id]
            );
        }
        $order = null;
        $paymentCompleted = $splitPayment || in_array($paymentMethod, [Order::PAYMENT_WALLET, Order::PAYMENT_DPBV, 'kd_credit'], true);
        DB::transaction(function () use ($user, $walletOwner, $data, $paymentMethod, $paymentBreakdown, $splitPayment, $walletAmt, $kdAmt, $dpbvAmt, $request, $orderKdId, $orderCustomerName, $deliveryType, $shippingAddress, $shippingCity, $shippingState, $shippingPostal, $shippingPhone, $branchUserId, $isHeadquarters, $stockOwner, $stockUserId, $roleName, $paymentCompleted, $serviceCenterForDistributor, $scReferralCode, $collectionBranch, &$order) {
            if (! $orderKdId || ! $orderCustomerName) {
                Guest::firstOrCreate(
                    ['session_id' => $request->session()->getId(), 'user_id' => $user->id],
                    ['session_id' => $request->session()->getId(), 'user_id' => $user->id]
                );
            }
            $order = Order::create([
                'user_id' => $user->id,
                'branch_user_id' => $branchUserId,
                'collection_branch_id' => $collectionBranch?->id,
                'kd_id' => $orderKdId,
                'customer_name' => $orderCustomerName,
                'delivery_type' => $deliveryType,
                'invoice_number' => Order::generateOrderNumber(),
                'subtotal' => $data['cartSubtotal'],
                'total_bv' => $data['cartBv'],
                'total_pv' => $data['cartPv'],
                'payment_method' => $paymentMethod === 'dpbv' ? Order::PAYMENT_DPBV : $paymentMethod,
                'pos_amount_paid' => $splitPayment && (float) ($paymentBreakdown['pos'] ?? 0) > 0 ? $paymentBreakdown['pos'] : null,
                'payment_breakdown' => $paymentBreakdown,
                'status' => ($splitPayment || $paymentMethod === Order::PAYMENT_WALLET || $paymentMethod === Order::PAYMENT_DPBV || $paymentMethod === 'kd_credit') ? Order::STATUS_PAID : Order::STATUS_PENDING,
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'shipping_state' => $shippingState,
                'shipping_postal_code' => $shippingPostal,
                'shipping_phone' => $shippingPhone,
                'coupon_id' => $data['coupon'] ? $data['coupon']->id : null,
                'coupon_code' => $data['coupon'] ? $data['coupon']->code : null,
                'discount_amount' => $data['discountAmount'] ?? 0,
                'sc_referral_code' => $scReferralCode !== '' ? $scReferralCode : null,
                'notes' => $request->input('notes'),
            ]);

            foreach ($data['cartItems'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $item->product->item_code,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'bv' => $item->product->bv,
                    'pv' => $item->product->pv,
                ]);

            }

            if ($paymentCompleted && ! $collectionBranch) {
                $this->deductStockForCompletedPayment($data['cartItems'], $isHeadquarters, $stockUserId, $roleName, (int) $stockOwner->id);
                $order->update(['stock_deducted_at' => now()]);
            }

            $walletDebit = $splitPayment ? (float) $walletAmt : ($paymentMethod === Order::PAYMENT_WALLET ? (float) $data['cartTotal'] : 0);
            if ($walletDebit > 0 && $walletOwner) {
                $debitAmount = $walletDebit;

                // Distributor special case:
                // - Debit distributor wallet
                // - Credit the matched Service Center wallet
                if ($user->role?->name === 'distributor' && $serviceCenterForDistributor) {
                    $walletOwner->decrement('wallet_balance', $debitAmount);
                    $walletBalanceAfter = (float) $walletOwner->fresh()->wallet_balance;
                    WalletTransaction::create([
                        'user_id' => $walletOwner->id,
                        'type' => WalletTransaction::TYPE_DEBIT,
                        'amount' => $debitAmount,
                        'balance_after' => $walletBalanceAfter,
                        'reference' => 'Order #'.$order->id,
                    ]);

                    $serviceCenterForDistributor->increment('wallet_balance', $debitAmount);
                    $scBalanceAfter = (float) $serviceCenterForDistributor->fresh()->wallet_balance;
                    WalletTransaction::create([
                        'user_id' => $serviceCenterForDistributor->id,
                        'type' => WalletTransaction::TYPE_CREDIT,
                        'amount' => $debitAmount,
                        'balance_after' => $scBalanceAfter,
                        'reference' => 'Order #'.$order->id,
                    ]);
                } else {
                    // Debit wallet from the wallet owner (parent for cashiers, self otherwise)
                    $walletOwner->decrement('wallet_balance', $debitAmount);
                    $balanceAfter = (float) $walletOwner->fresh()->wallet_balance;
                    WalletTransaction::create([
                        'user_id' => $walletOwner->id,
                        'type' => WalletTransaction::TYPE_DEBIT,
                        'amount' => $debitAmount,
                        'balance_after' => $balanceAfter,
                        'reference' => 'Order #'.$order->id,
                    ]);
                }

                // Referral payout: if customer entered a Service Center referral code,
                // credit that Service Center's wallet for this paid order.
                if ($scReferralCode !== '') {
                    $refServiceCenter = $this->resolveServiceCenterByCode($scReferralCode);
                    $alreadyCreditedScId = ($user->role?->name === 'distributor' && $serviceCenterForDistributor)
                        ? (int) $serviceCenterForDistributor->id
                        : null;

                    if (
                        $refServiceCenter
                        && (int) $refServiceCenter->id !== (int) $walletOwner->id
                        && ($alreadyCreditedScId === null || (int) $refServiceCenter->id !== $alreadyCreditedScId)
                    ) {
                        $refServiceCenter->increment('wallet_balance', $debitAmount);
                        $refBalanceAfter = (float) $refServiceCenter->fresh()->wallet_balance;
                        WalletTransaction::create([
                            'user_id' => $refServiceCenter->id,
                            'type' => WalletTransaction::TYPE_CREDIT,
                            'amount' => $debitAmount,
                            'balance_after' => $refBalanceAfter,
                            'reference' => 'Referral payout (Order #'.$order->id.')',
                            'status' => WalletTransaction::STATUS_ACCEPTED,
                            'approved_at' => now(),
                        ]);
                    } elseif (! $refServiceCenter) {
                        \Log::warning('Checkout referral code not found', [
                            'order_id' => $order->id,
                            'sc_referral_code' => $scReferralCode,
                            'user_id' => $user->id,
                        ]);
                    }
                }
            }

            // Mark coupon as used
            if ($data['coupon']) {
                $data['coupon']->increment('used_count');
            }

            // Deduct KD Registration credit if paying with credit
            $creditDebit = $splitPayment ? (float) $kdAmt : ($paymentMethod === 'kd_credit' ? (float) $data['cartTotal'] : 0);
            if ($creditDebit > 0 && $orderKdId) {
                $kdRegistration = KdRegistration::where('kd_no', $orderKdId)->first();
                if ($kdRegistration) {
                    $currentBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                    $newBalance = $currentBalance - $creditDebit;

                    KdRegistrationCredit::create([
                        'kd_registration_id' => $kdRegistration->id,
                        'type' => KdRegistrationCredit::TYPE_DEBIT,
                        'amount' => $creditDebit,
                        'balance_after' => $newBalance,
                        'reference' => 'Order #'.$order->invoice_number,
                        'notes' => 'Payment for order',
                        'created_by_user_id' => $user->id,
                    ]);
                }
            }

            // Deduct DPBV if paying with DPBV
            $dpbvDebit = $splitPayment ? (float) $dpbvAmt : ($paymentMethod === Order::PAYMENT_DPBV ? (float) $data['cartTotal'] : 0);
            if ($dpbvDebit > 0) {
                $amountToDeduct = $dpbvDebit;

                // Calculate how much DPBV to deduct (reverse calculation: naira / 990 / 0.95)
                $dpbvToDeduct = $amountToDeduct / 990 / 0.95;

                // Create a negative DPBV collection record to track usage
                DpbvCollection::create([
                    'no' => null,
                    'code' => $order->kd_id ?? 'USED',
                    'name' => $user->name,
                    'record_date' => now(),
                    'sc' => 'CHECKOUT',
                    'dpbv' => -$dpbvToDeduct, // Negative to deduct
                    'user_id' => $user->id,
                ]);
            }
        });

        $request->session()->forget('cart');
        $request->session()->forget(['kd_id', 'customer_name', 'collection_branch_id']);

        $order->load(['user', 'items']);
        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: '.$e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Order placed successfully. Thank you!');
    }

    public function saveToDraft(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $user = $request->user();
        $kdId = trim((string) ($request->input('kd_id') ?? $request->session()->get('kd_id', '')));
        $customerName = trim((string) ($request->input('customer_name') ?? $request->session()->get('customer_name', '')));
        $orderKdId = $kdId !== '' ? $kdId : null;
        $orderCustomerName = $customerName !== '' ? $customerName : null;

        // Determine stock owner for drafts (HQ/Branch/SC/Annex or their cashier)
        $roleName = $user->role?->name ?? '';
        $stockOwner = $user;
        if (in_array($roleName, ['cashier', 'distributor'], true) && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        $isHeadquarters = ($roleName === 'headquarters');
        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex']) ? (int) $stockOwner->id : null;

        foreach ($data['cartItems'] as $item) {
            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($stockOwner->id, $item->product->id);
                if ($avail < $item->quantity) {
                    return back()->with('error', "Insufficient Headquarters stock for {$item->product->name}. Available: {$avail}.");
                }
            } elseif ($stockUserId) {
                $avail = $this->getStockForUser($stockUserId, $roleName, $item->product->id);
                if ($avail < $item->quantity) {
                    return back()->with('error', "Insufficient stock for {$item->product->name}. Available: {$avail}.");
                }
            }
        }
        $branchUserId = ($isHeadquarters || $stockUserId) ? (int) $stockOwner->id : null;

        $deliveryType = $request->input('delivery_type', 'ship');
        $shippingAddress = $deliveryType === 'walk_in' ? 'Walk-in (Pick up)' : $request->input('shipping_address', '');
        $shippingCity = $deliveryType === 'walk_in' ? '' : $request->input('shipping_city', '');
        $shippingState = $deliveryType === 'walk_in' ? '' : $request->input('shipping_state', '');
        $shippingPostal = $deliveryType === 'walk_in' ? '' : $request->input('shipping_postal_code', '');
        $shippingPhone = $request->input('shipping_phone', '') ?: ($request->user()->phone ?? '');

        $order = null;
        DB::transaction(function () use ($request, $user, $data, $deliveryType, $shippingAddress, $shippingCity, $shippingState, $shippingPostal, $shippingPhone, $orderKdId, $orderCustomerName, $branchUserId, &$order) {
            $order = Order::create([
                'user_id' => $user->id,
                'branch_user_id' => $branchUserId,
                'kd_id' => $orderKdId,
                'customer_name' => $orderCustomerName,
                'delivery_type' => $deliveryType,
                'invoice_number' => Order::generateOrderNumber(),
                'subtotal' => $data['cartSubtotal'],
                'total_bv' => $data['cartBv'],
                'total_pv' => $data['cartPv'],
                'payment_method' => Order::PAYMENT_PAY_ON_DELIVERY,
                'status' => Order::STATUS_DRAFT,
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'shipping_state' => $shippingState,
                'shipping_postal_code' => $shippingPostal,
                'shipping_phone' => $shippingPhone,
                'sc_referral_code' => $request->input('sc_referral_code'),
                'notes' => $request->input('notes'),
            ]);

            foreach ($data['cartItems'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $item->product->item_code,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'line_total' => $item->line_total,
                    'bv' => $item->product->bv,
                    'pv' => $item->product->pv,
                ]);
            }
        });

        $request->session()->forget('cart');
        $request->session()->forget(['kd_id', 'customer_name']);

        return redirect()->route('home')->with('success', 'Order saved as draft. You can complete it later from My Orders. Cart cleared – start shopping again.');
    }

    public function restoreDraft(Request $request, Order $order)
    {
        if (! $request->user() || $order->user_id != $request->user()->id) {
            abort(404);
        }

        if ($order->status !== Order::STATUS_DRAFT) {
            return redirect()->route('orders.index')
                ->with('error', 'This order is not a draft.');
        }

        $order->load('items');

        $cart = session()->get('cart', []);

        foreach ($order->items as $item) {
            $cart[$item->item_code] = ($cart[$item->item_code] ?? 0) + $item->quantity;
        }

        session()->put('cart', $cart);
        session()->put('kd_id', $order->kd_id ?? '');
        session()->put('customer_name', $order->customer_name ?? '');

        $order->delete();

        return redirect()->route('checkout.show')
            ->with('success', 'Draft restored to cart.');
    }

    public function placeDraftFromWallet(Request $request, Order $order)
    {
        if ((int) $order->user_id !== (int) $request->user()->id) {
            abort(404);
        }
        if ($order->status !== Order::STATUS_DRAFT) {
            return redirect()->route('orders.index')->with('error', 'This order is not a draft.');
        }

        $user = $request->user();
        $subtotal = (float) $order->subtotal;

        // Determine stock owner when placing a single draft from wallet
        $roleName = $user->role?->name ?? '';
        $stockOwner = $user;
        if (in_array($roleName, ['cashier', 'distributor'], true) && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex']) ? (int) $stockOwner->id : null;
        $isHeadquarters = $roleName === 'headquarters';
        $branchUserId = $stockUserId;

        // Check stock availability before placing draft order
        $order->load('items');
        foreach ($order->items as $item) {
            $product = \App\Models\Product::where('item_code', $item->item_code)->first();
            if ($product) {
                if ($isHeadquarters) {
                    $avail = HeadquartersStock::getQuantity($user->id, $product->id);
                    if ($avail < $item->quantity) {
                        return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient Headquarters stock for {$item->product_name}. Available: {$avail}. Remove from cart or reduce quantity.");
                    }
                } elseif ($stockUserId) {
                    $avail = $this->getStockForUser($stockUserId, $user->role?->name, $product->id);
                    if ($avail < $item->quantity) {
                        return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient stock for {$item->product_name}. Available: {$avail}. Remove from cart or reduce quantity.");
                    }
                } else {
                    // For regular users, check main product stock
                    if ($product->stock < $item->quantity) {
                        return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient stock for {$item->product_name}. Available: {$product->stock}. Remove from cart or reduce quantity.");
                    }
                }
            }
        }

        if (! $walletOwner->canPayWithWallet($subtotal)) {
            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', 'Insufficient wallet balance. Need ₦'.number_format($subtotal, 0).' – you have ₦'.number_format($walletOwner->wallet_balance ?? 0, 0).'.');
        }

        DB::transaction(function () use ($walletOwner, $order, $subtotal, $branchUserId, $isHeadquarters, $stockOwner, $stockUserId, $roleName) {
            $order->update([
                'payment_method' => Order::PAYMENT_WALLET,
                'status' => Order::STATUS_PAID,
                'branch_user_id' => $branchUserId,
            ]);

            $order->load('items');
            $this->deductStockForCompletedPayment($order->items, $isHeadquarters, $stockUserId, $roleName, (int) $stockOwner->id);
            $order->update(['stock_deducted_at' => now()]);

            $walletOwner->decrement('wallet_balance', $subtotal);
            $balanceAfter = (float) $walletOwner->fresh()->wallet_balance;
            WalletTransaction::create([
                'user_id' => $walletOwner->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $subtotal,
                'balance_after' => $balanceAfter,
                'reference' => 'Order #'.$order->id,
            ]);
        });

        $request->session()->forget(['kd_id', 'customer_name']);

        $order->load(['user', 'items']);
        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: '.$e->getMessage());
        }

        return redirect()->route('orders.receipt', $order)->with('success', 'Order placed successfully. ₦'.number_format($subtotal, 0).' deducted from wallet.');
    }

    public function placeAllDraftsFromWallet(Request $request)
    {
        $user = $request->user();
        $drafts = $user->orders()->where('status', Order::STATUS_DRAFT)->with('items')->get();

        if ($drafts->isEmpty()) {
            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', 'No draft orders to place.');
        }

        $totalAmount = $drafts->sum('subtotal');
        if (! $user->canPayWithWallet($totalAmount)) {
            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', 'Insufficient wallet balance. Need ₦'.number_format($totalAmount, 0).' – you have ₦'.number_format($user->wallet_balance ?? 0, 0).'.');
        }

        // Determine stock owner when placing all drafts from wallet
        $roleName = $user->role?->name ?? '';
        $stockOwner = $user;
        if (in_array($roleName, ['cashier', 'distributor'], true) && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        // Check stock availability for all drafts before placing
        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex']) ? (int) $stockOwner->id : null;
        $isHeadquarters = $roleName === 'headquarters';
        $branchUserId = $stockUserId;

        foreach ($drafts as $order) {
            foreach ($order->items as $item) {
                $product = \App\Models\Product::where('item_code', $item->item_code)->first();
                if ($product) {
                    if ($isHeadquarters) {
                        $avail = HeadquartersStock::getQuantity($stockOwner->id, $product->id);
                        if ($avail < $item->quantity) {
                            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient Headquarters stock for {$item->product_name} in order #{$order->invoice_number}. Available: {$avail}.");
                        }
                    } elseif ($stockUserId) {
                        $avail = $this->getStockForUser($stockUserId, $roleName, $product->id);
                        if ($avail < $item->quantity) {
                            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient stock for {$item->product_name} in order #{$order->invoice_number}. Available: {$avail}.");
                        }
                    } else {
                        if ($product->stock < $item->quantity) {
                            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient stock for {$item->product_name} in order #{$order->invoice_number}. Available: {$product->stock}.");
                        }
                    }
                }
            }
        }

        DB::transaction(function () use ($user, $walletOwner, $drafts, $branchUserId, $isHeadquarters, $stockOwner, $stockUserId, $roleName) {
            foreach ($drafts as $order) {
                $subtotal = (float) $order->subtotal;
                $order->update([
                    'payment_method' => Order::PAYMENT_WALLET,
                    'status' => Order::STATUS_PAID,
                    'branch_user_id' => $branchUserId,
                ]);

                $order->load('items');
                $this->deductStockForCompletedPayment($order->items, $isHeadquarters, $stockUserId, $roleName, (int) $stockOwner->id);
                $order->update(['stock_deducted_at' => now()]);

                $walletOwner->decrement('wallet_balance', $subtotal);
                $balanceAfter = (float) $walletOwner->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $walletOwner->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $subtotal,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #'.$order->id,
                ]);

                $order->load(['user', 'items']);
                try {
                    Mail::to($user->email)->send(new OrderConfirmationMail($order));
                } catch (\Throwable $e) {
                    \Log::warning('Order confirmation email failed: '.$e->getMessage());
                }
            }
        });

        $request->session()->forget(['kd_id', 'customer_name']);

        $firstOrder = $drafts->first();
        $placedOrderIds = $drafts->pluck('id')->toArray();

        $request->session()->put('placed_order_ids', $placedOrderIds);

        return redirect()->route('orders.receipt', $firstOrder)
            ->with('success', 'All '.$drafts->count().' draft(s) placed successfully. ₦'.number_format($totalAmount, 0).' deducted from wallet.');
    }

    /** Get stock quantity for Branch/Service Center/Annex user. */
    private function getStockForUser(int $userId, ?string $role, int $productId): int
    {
        if ($role === 'branch') {
            return BranchStock::getQuantity($userId, $productId);
        }
        if ($role === 'service_center') {
            return ServiceCenterStock::getQuantity($userId, $productId);
        }
        if ($role === 'annex') {
            return AnnexStock::getQuantity($userId, $productId);
        }

        return BranchStock::getQuantity($userId, $productId);
    }

    /**
     * Take stock as soon as checkout payment is completed.
     *
     * @param  iterable<int, object>  $items
     */
    private function deductStockForCompletedPayment(iterable $items, bool $isHeadquarters, ?int $stockUserId, string $roleName, int $stockOwnerId): void
    {
        foreach ($items as $item) {
            $product = $item->product ?? Product::where('item_code', $item->item_code ?? '')->first();
            if (! $product) {
                continue;
            }
            $qty = (int) $item->quantity;
            if ($qty < 1) {
                continue;
            }

            if ($isHeadquarters) {
                HeadquartersStock::decrementStock($stockOwnerId, $product->id, $qty);
                continue;
            }

            if ($stockUserId) {
                if ($roleName === 'service_center') {
                    ServiceCenterStock::decrementStock($stockUserId, $product->id, $qty);
                } elseif ($roleName === 'annex') {
                    AnnexStock::decrementStock($stockUserId, $product->id, $qty);
                } else {
                    BranchStock::decrementStock($stockUserId, $product->id, $qty);
                }
                continue;
            }

            if ((int) $product->stock >= $qty) {
                $product->decrement('stock', $qty);
            }
        }
    }

    /**
     * Buy with DPBV - auto-generate KD NO and place order with DPBV (subtotal = 0).
     */
    public function buyWithDpbv(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $user = $request->user();
        if (! $user) {
            return redirect()->route('login')->with('error', 'Please login to buy with DPBV.');
        }

        // Check DPBV balance
        $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
        if (round($dpbvNairaEquivalent, 2) < round((float) $data['cartSubtotal'], 2)) {
            return redirect()->route('home')->with('error', 'Insufficient DPBV balance. You have ₦'.number_format($dpbvNairaEquivalent, 2).' available.');
        }

        // Check if all products allow DPBV
        $productsNotAllowed = [];
        foreach ($data['cartItems'] as $item) {
            if (! ($item->product->can_use_dpbv ?? true)) {
                $productsNotAllowed[] = $item->product->name;
            }
        }
        if (! empty($productsNotAllowed)) {
            return redirect()->route('home')->with('error', 'The following products cannot be purchased with DPBV: '.implode(', ', $productsNotAllowed).'. Please remove them from your cart.');
        }

        // Auto-generate KD NO and name
        $baseKd = 'KD-'.$user->id.'-';
        $existing = KdCustomer::where('kd_no', 'like', $baseKd.'%')->max('kd_no');
        $seq = 1;
        if ($existing) {
            $parts = explode('-', $existing);
            $seq = (int) (end($parts) ?: 0) + 1;
        }
        $kdNo = $baseKd.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $customerName = trim($user->name ?? $user->email ?? 'Customer');

        $kd = KdCustomer::updateOrCreate(
            ['kd_no' => $kdNo],
            ['customer_name' => $customerName, 'user_id' => $user->id]
        );

        $stockUserId = null;
        $isHeadquarters = $user->role?->name === 'headquarters';
        if ($user->role?->name === 'branch') {
            $stockUserId = (int) $user->id;
        } elseif ($user->role?->name === 'service_center') {
            $stockUserId = (int) $user->id;
        } elseif ($user->role?->name === 'annex') {
            $stockUserId = (int) $user->id;
        }
        $branchUserId = $stockUserId;

        // Check stock availability
        foreach ($data['cartItems'] as $item) {
            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($user->id, $item->product->id);
                if ($avail < $item->quantity) {
                    return redirect()->route('home')->with('error', "Insufficient Headquarters stock for {$item->product->name}. Available: {$avail}.");
                }
            } elseif ($stockUserId) {
                $avail = $this->getStockForUser($stockUserId, $user->role?->name, $item->product->id);
                if ($avail < $item->quantity) {
                    return redirect()->route('home')->with('error', "Insufficient stock for {$item->product->name}. Available: {$avail}.");
                }
            } else {
                if ($item->product->stock < $item->quantity) {
                    return redirect()->route('home')->with('error', "Insufficient stock for {$item->product->name}. Available: {$item->product->stock}.");
                }
            }
        }

        $order = null;
        DB::transaction(function () use ($user, $data, $kdNo, $customerName, $branchUserId, $isHeadquarters, &$order) {
            // Create order with is_dpbv_order = true and subtotal = 0
            $order = Order::create([
                'user_id' => $user->id,
                'branch_user_id' => $branchUserId,
                'kd_id' => $kdNo,
                'customer_name' => $customerName,
                'delivery_type' => Order::DELIVERY_WALK_IN,
                'invoice_number' => Order::generateOrderNumber(),
                'subtotal' => 0, // Set to 0 for DPBV orders
                'total_bv' => $data['cartBv'],
                'total_pv' => $data['cartPv'],
                'payment_method' => Order::PAYMENT_DPBV,
                'status' => Order::STATUS_PAID,
                'is_dpbv_order' => true,
                'shipping_address' => 'DPBV Order – Walk-in',
                'shipping_city' => '',
                'shipping_state' => '',
                'shipping_postal_code' => '',
                'shipping_phone' => $user->phone ?? '',
            ]);

            foreach ($data['cartItems'] as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $item->product->item_code,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'unit_price' => 0, // Set to 0 for DPBV orders
                    'line_total' => 0, // Set to 0 for DPBV orders
                    'bv' => $item->product->bv,
                    'pv' => $item->product->pv,
                ]);

            }

            $this->deductStockForCompletedPayment($data['cartItems'], $isHeadquarters, $stockUserId, $user->role?->name ?? '', (int) $user->id);
            $order->update(['stock_deducted_at' => now()]);

            // Deduct DPBV
            $amountToDeduct = $data['cartSubtotal'];
            $dpbvToDeduct = $amountToDeduct / 990 / 0.95;

            DpbvCollection::create([
                'no' => null,
                'code' => $kdNo,
                'name' => $customerName,
                'record_date' => now(),
                'sc' => 'DPBV_ORDER',
                'dpbv' => -$dpbvToDeduct,
                'user_id' => $user->id,
            ]);
        });

        $request->session()->forget('cart');
        $request->session()->put('kd_id', $kdNo);
        $request->session()->put('customer_name', $customerName);

        $order->load(['user', 'items']);
        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: '.$e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Order placed successfully with DPBV! KD NO: '.$kdNo);
    }

    public function validateScCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $code = trim((string) $request->input('code'));
        $variants = array_values(array_unique(array_filter([
            $code,
            strtoupper($code),
            strtolower($code),
        ], fn ($v) => $v !== '')));

        $user = User::whereIn('service_center_code', $variants)
            ->whereHas('role', function ($query) {
                $query->where('name', Role::SERVICE_CENTER);
            })
            ->first();

        if ($user) {
            return response()->json([
                'valid' => true,
                'name' => $user->name,
            ]);
        }

        return response()->json([
            'valid' => false,
            'message' => 'Invalid Service Center code.',
        ]);
    }
}
