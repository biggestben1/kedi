<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\AnnexStock;
use App\Models\Bank;
use App\Models\BranchStock;
use App\Models\CollectionCenterMove;
use App\Models\Coupon;
use App\Models\DpbvCollection;
use App\Models\HeadquartersStock;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\KdRegistrationCredit;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\PosMachine;
use App\Models\Product;
use App\Models\Role;
use App\Models\ServiceCenterStock;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class OrderGroupController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $groups = $user->orderGroups()
            ->withCount(['orders', 'draftOrders'])
            ->withSum('orders', 'subtotal')
            ->latest()
            ->paginate(20);

        $activeGroupId = (int) $request->session()->get('order_group_id', 0);
        $openGroups = $user->orderGroups()
            ->where('status', OrderGroup::STATUS_OPEN)
            ->withCount(['orders', 'draftOrders'])
            ->latest()
            ->get();
        $activeGroup = $openGroups->firstWhere('id', $activeGroupId);

        return view('order-groups.index', compact('groups', 'activeGroupId', 'openGroups', 'activeGroup'));
    }

    public function create(Request $request)
    {
        return view('order-groups.create', [
            'pageTitle' => 'Create Order Group',
            'customerMenuActive' => 'order-groups',
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $user = $request->user();

        $group = OrderGroup::create([
            'user_id' => $user->id,
            'name' => trim($data['name']),
            'status' => OrderGroup::STATUS_OPEN,
            'started_at' => now(),
        ]);

        $this->activateSession($request, $group);
        // Each transaction needs its own KEDI session — clear any previous one.
        $request->session()->forget(['kd_id', 'customer_name']);

        return redirect()
            ->route('shop')
            ->with('success', 'Group "'.$group->displayName().'" started. Enter a KEDI NO and name for the first transaction, then shop and add to group.');
    }

    /**
     * Add the current cart as a new draft order inside this group (from shop).
     */
    public function addCart(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is closed.');
        }

        $this->activateSession($request, $orderGroup);

        $cart = $request->session()->get('cart', []);
        if (array_sum($cart) < 1) {
            return redirect()->route('shop')->with('error', 'Your cart is empty. Add products first, then Add to group.');
        }

        $user = $request->user();
        $user->load(['role', 'createdBy.role']);
        // Transaction KEDI comes from the current sales session only (not the group).
        $kdId = trim((string) $request->session()->get('kd_id', ''));
        $customerName = trim((string) $request->session()->get('customer_name', ''));
        if ($kdId === '' || $customerName === '') {
            return redirect()->route('shop')->with('error', 'Enter KEDI NO and customer name for this transaction first.');
        }

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
        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex'], true) ? (int) $stockOwner->id : null;
        $branchUserId = ($isHeadquarters || $stockUserId) ? (int) $stockOwner->id : null;

        $cartItems = [];
        $cartSubtotal = 0;
        $cartBv = 0;
        $cartPv = 0;
        foreach ($cart as $itemCode => $qty) {
            $product = Product::where('item_code', $itemCode)->where('is_active', true)->first();
            if (! $product || $qty < 1) {
                continue;
            }
            $unitPrice = $product->getPriceForUser($user);
            $lineTotal = $unitPrice * $qty;
            $cartItems[] = (object) [
                'product' => $product,
                'quantity' => (int) $qty,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ];
            $cartSubtotal += $lineTotal;
            $cartBv += $product->bv * $qty;
            $cartPv += $product->pv * $qty;

            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($stockOwner->id, $product->id);
                if ($avail < $qty) {
                    return redirect()->route('shop')->with('error', "Insufficient Headquarters stock for {$product->name}. Available: {$avail}.");
                }
            } elseif ($stockUserId) {
                $avail = $this->getStockForUser($stockUserId, $roleName, $product->id);
                if ($avail < $qty) {
                    return redirect()->route('shop')->with('error', "Insufficient stock for {$product->name}. Available: {$avail}.");
                }
            }
        }

        if ($cartItems === []) {
            return redirect()->route('shop')->with('error', 'Your cart is empty.');
        }

        DB::transaction(function () use ($user, $orderGroup, $cartItems, $cartSubtotal, $cartBv, $cartPv, $branchUserId, $kdId, $customerName) {
            $order = Order::create([
                'user_id' => $user->id,
                'order_group_id' => $orderGroup->id,
                'branch_user_id' => $branchUserId,
                'kd_id' => $kdId !== '' ? $kdId : null,
                'customer_name' => $customerName !== '' ? $customerName : null,
                'delivery_type' => Order::DELIVERY_WALK_IN,
                'invoice_number' => Order::generateOrderNumber(),
                'subtotal' => $cartSubtotal,
                'total_bv' => $cartBv,
                'total_pv' => $cartPv,
                'payment_method' => Order::PAYMENT_PAY_ON_DELIVERY,
                'status' => Order::STATUS_DRAFT,
                'shipping_address' => 'Walk-in (Pick up)',
                'shipping_city' => '',
                'shipping_state' => '',
                'shipping_postal_code' => '',
                'shipping_phone' => $user->phone ?? '',
            ]);

            foreach ($cartItems as $item) {
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

            $orderGroup->update([
                'total_amount' => (float) $orderGroup->draftOrders()->sum('subtotal'),
            ]);
        });

        $request->session()->forget('cart');
        // Close this KEDI transaction session; keep the group open for the next transaction.
        $request->session()->forget(['kd_id', 'customer_name']);
        $request->session()->put('order_group_id', $orderGroup->id);

        return redirect()
            ->route('shop')
            ->with('success', 'Transaction added to group "'.$orderGroup->displayName().'". Enter the next KEDI NO and name to add another, or pay the group when finished.');
    }

    public function edit(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        return view('order-groups.edit', [
            'group' => $orderGroup,
            'pageTitle' => 'Edit Order Group',
            'customerMenuActive' => 'order-groups',
        ]);
    }

    public function update(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $orderGroup->update([
            'name' => trim($data['name']),
        ]);

        return redirect()
            ->route('order-groups.index')
            ->with('success', 'Group updated.');
    }

    public function destroy(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if ((int) $request->session()->get('order_group_id') === (int) $orderGroup->id) {
            $request->session()->forget('order_group_id');
        }

        // Keep orders; only unlink from group.
        $orderGroup->orders()->update(['order_group_id' => null]);
        $orderGroup->delete();

        return redirect()
            ->route('order-groups.index')
            ->with('success', 'Group deleted.');
    }

    public function show(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        $orderGroup->load([
            'collectionBranch',
            'orders' => fn ($q) => $q->with(['items', 'collectionBranch'])->latest(),
        ]);
        $paymentData = $this->groupPaymentViewData($request, $orderGroup);
        $isActive = (int) $request->session()->get('order_group_id') === (int) $orderGroup->id;

        $availableDrafts = collect();
        if ($orderGroup->isOpen()) {
            $availableDrafts = $this->attachableDraftsQuery($request->user(), $orderGroup)
                ->with('items')
                ->latest()
                ->get();
        }

        $collectionBranches = User::whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone']);

        $currentCollectionBranchIds = $orderGroup->orders
            ->pluck('collection_branch_id')
            ->filter()
            ->unique()
            ->values();
        $currentCollectionBranch = $orderGroup->collectionBranch
            ?: ($currentCollectionBranchIds->count() === 1
                ? $collectionBranches->firstWhere('id', (int) $currentCollectionBranchIds->first())
                : null);

        return view('order-groups.show', array_merge($paymentData, [
            'group' => $orderGroup,
            'isActive' => $isActive,
            'availableDrafts' => $availableDrafts,
            'collectionBranches' => $collectionBranches,
            'currentCollectionBranch' => $currentCollectionBranch,
            'currentCollectionBranchIds' => $currentCollectionBranchIds,
            'pageTitle' => $orderGroup->displayName(),
            'customerMenuActive' => 'order-groups',
        ]));
    }

    /**
     * Move all uncollected orders in this group to another collection center (branch).
     */
    public function moveCollectionCenter(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        $data = $request->validate([
            'collection_branch_id' => ['required', 'integer'],
        ]);

        $branch = User::where('id', $data['collection_branch_id'])
            ->whereHas('role', fn ($q) => $q->where('name', Role::BRANCH))
            ->first();

        if (! $branch) {
            return back()->withErrors(['collection_branch_id' => 'Choose a valid collection center (branch).'])->withInput();
        }

        $orders = $orderGroup->orders()
            ->whereNull('collected_at')
            ->where('status', '!=', Order::STATUS_CANCELLED)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No movable orders in this group. Collected or cancelled orders cannot be moved.');
        }

        $blocked = $orders->filter(function (Order $order) {
            // Already deducted at seller and not routed to a collection center yet —
            // assigning a center would risk a second stock deduction on collect.
            return $order->stock_deducted_at && ! $order->collection_branch_id;
        });

        if ($blocked->isNotEmpty()) {
            return back()->with(
                'error',
                'Cannot move '.$blocked->count().' order(s) that already had stock deducted without a collection center. Contact support or recreate those orders for collection.'
            );
        }

        $moved = 0;
        $moverId = $request->user()->id;
        DB::transaction(function () use ($orders, $branch, $orderGroup, $moverId, &$moved) {
            $orderGroup->update(['collection_branch_id' => $branch->id]);

            foreach ($orders as $order) {
                $fromId = $order->collection_branch_id ? (int) $order->collection_branch_id : null;
                if ($fromId === (int) $branch->id) {
                    continue;
                }

                $order->update(['collection_branch_id' => $branch->id]);

                CollectionCenterMove::create([
                    'order_id' => $order->id,
                    'order_group_id' => $orderGroup->id,
                    'from_branch_user_id' => $fromId,
                    'to_branch_user_id' => $branch->id,
                    'moved_by_user_id' => $moverId,
                    'reason' => 'Moved via order group',
                ]);

                $moved++;
            }
        });

        if ($moved === 0) {
            return redirect()
                ->route('order-groups.show', $orderGroup)
                ->with('message', 'All orders are already at '.$branch->name.'.');
        }

        return redirect()
            ->route('order-groups.show', $orderGroup)
            ->with('success', $moved.' order(s) moved to collection center "'.$branch->name.'".');
    }

    /**
     * Attach existing draft order(s) that were saved earlier into this open group.
     */
    public function addDrafts(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is closed.');
        }

        $orderIds = $request->input('order_ids', []);
        if (! is_array($orderIds)) {
            $orderIds = [$orderIds];
        }
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));

        if ($orderIds === []) {
            return back()->with('error', 'Select at least one draft order to add.');
        }

        $drafts = $this->attachableDraftsQuery($request->user(), $orderGroup)
            ->whereIn('id', $orderIds)
            ->get();

        if ($drafts->isEmpty()) {
            return back()->with('error', 'No matching draft orders found to add. They may already be in this group, or belong to another account.');
        }

        DB::transaction(function () use ($drafts, $orderGroup) {
            foreach ($drafts as $draft) {
                $draft->update(['order_group_id' => $orderGroup->id]);
            }

            $orderGroup->update([
                'total_amount' => (float) $orderGroup->draftOrders()->sum('subtotal'),
            ]);
        });

        $this->activateSession($request, $orderGroup);

        $count = $drafts->count();

        return redirect()
            ->route('order-groups.show', $orderGroup)
            ->with('success', $count.' draft order'.($count === 1 ? '' : 's').' added to "'.$orderGroup->displayName().'".');
    }

    public function payForm(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is already closed.');
        }

        $orderGroup->load(['orders' => fn ($q) => $q->with('items')->latest()]);
        $paymentData = $this->groupPaymentViewData($request, $orderGroup);

        if ($paymentData['drafts']->isEmpty()) {
            return redirect()
                ->route('order-groups.show', $orderGroup)
                ->with('error', 'No unpaid orders in this group yet. Add orders from checkout first.');
        }

        return view('order-groups.pay', array_merge($paymentData, [
            'group' => $orderGroup,
            'pageTitle' => 'Pay – '.$orderGroup->displayName(),
            'customerMenuActive' => 'order-groups',
            'cartCount' => array_sum($request->session()->get('cart', [])),
        ]));
    }

    public function resume(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is already closed.');
        }

        $this->activateSession($request, $orderGroup);
        $request->session()->forget(['kd_id', 'customer_name']);

        if ($request->boolean('go_shop') || $request->input('redirect') === 'shop') {
            return redirect()
                ->route('shop')
                ->with('success', 'Group "'.$orderGroup->displayName().'" is now active. Enter a KEDI NO and name for the next transaction.');
        }

        return redirect()
            ->route('order-groups.index')
            ->with('success', 'Switched session to "'.$orderGroup->displayName().'". Orders you add will go into this group.');
    }

    public function end(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if ((int) $request->session()->get('order_group_id') === (int) $orderGroup->id) {
            $request->session()->forget(['order_group_id']);
        }

        return redirect()->route('order-groups.index')->with('message', 'Group paused. Click Activate group on any open group to continue.');
    }

    public function cancel(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is already closed.');
        }

        $orderGroup->update([
            'status' => OrderGroup::STATUS_CANCELLED,
            'completed_at' => now(),
        ]);

        // Free drafts so they can be added to another group later.
        $orderGroup->orders()
            ->where('status', Order::STATUS_DRAFT)
            ->update(['order_group_id' => null]);

        if ((int) $request->session()->get('order_group_id') === (int) $orderGroup->id) {
            $request->session()->forget('order_group_id');
        }

        return redirect()->route('order-groups.index')->with('success', 'Group cancelled. Draft orders are available to add to another group.');
    }

    public function pay(Request $request, OrderGroup $orderGroup)
    {
        $this->authorizeGroup($request, $orderGroup);

        if (! $orderGroup->isOpen()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'This group is already closed.');
        }

        $user = $request->user();
        $user->load(['role', 'createdBy.role']);
        $drafts = $orderGroup->orders()->where('status', Order::STATUS_DRAFT)->with('items')->get();

        if ($drafts->isEmpty()) {
            return redirect()->route('order-groups.show', $orderGroup)->with('error', 'No draft orders in this group to pay.');
        }

        $grossTotal = round((float) $drafts->sum('subtotal'), 2);
        $splitPayment = $request->boolean('split_payment');
        $request->validate([
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
            'coupon_code' => 'nullable|string|max:100',
        ]);

        $coupon = null;
        $discountAmount = 0.0;
        $couponCode = strtoupper(trim((string) ($request->input('coupon_code') ?: $request->session()->get('coupon_code', ''))));
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (! $coupon || ! $coupon->isValid()) {
                return back()->withErrors(['coupon_code' => 'Invalid or expired coupon code.'])->withInput();
            }
            $discountAmount = round(((float) $coupon->discount_percentage / 100) * $grossTotal, 2);
            $request->session()->put('coupon_code', $coupon->code);
        }

        $totalAmount = max(0, round($grossTotal - $discountAmount, 2));

        $paymentMethod = $splitPayment ? 'split' : ($request->input('payment_method') ?: Order::PAYMENT_WALLET);
        $walletOwner = $user->walletOwnerForShopping();
        $kdId = $this->normalizeKdNo(trim((string) ($request->input('kd_id') ?: $request->session()->get('kd_id', ''))));
        $customerName = trim((string) ($request->input('customer_name') ?: $request->session()->get('customer_name', '')));
        if ($kdId !== '') {
            $request->session()->put('kd_id', $kdId);
        }
        if ($customerName !== '') {
            $request->session()->put('customer_name', $customerName);
        }
        $paymentBreakdown = null;
        $walletAmt = 0.0;
        $kdAmt = 0.0;
        $dpbvAmt = 0.0;
        $paymentCompleted = in_array($paymentMethod, ['wallet', 'dpbv', 'kd_credit', 'split'], true)
            || ($paymentMethod === Order::PAYMENT_PAY_ON_DELIVERY ? false : true);

        if ($paymentMethod === Order::PAYMENT_PAY_ON_DELIVERY) {
            $paymentCompleted = false;
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

            if ($sum <= 0 && $totalAmount > 0) {
                return back()->withErrors(['split_payment' => 'Enter at least one payment amount.'])->withInput();
            }
            if ($totalAmount > 0 && $sum !== $totalAmount) {
                return back()->withErrors([
                    'split_payment' => 'Payment amounts must add up to the amount due (₦'.number_format($totalAmount, 2).').',
                ])->withInput();
            }

            $walletBalance = (float) ($walletOwner->wallet_balance ?? 0);
            if ($walletAmt > 0 && $walletBalance < $walletAmt) {
                return back()->withErrors(['split_wallet_amount' => 'Insufficient wallet balance.'])->withInput();
            }

            if ($kdAmt > 0) {
                if ($kdId === '') {
                    return back()->withErrors(['kd_id' => 'Credit code (KD NO) is required to pay any amount from KD Credit.'])->withInput();
                }
                $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
                if (! $kdRegistration) {
                    return back()->with('error', 'KD Registration not found for that credit code.');
                }
                $kdCreditBalance = (float) $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                if ($kdCreditBalance < $kdAmt) {
                    return back()->withErrors(['split_kd_credit_amount' => 'Insufficient KD Credit balance.'])->withInput();
                }
            }

            if ($dpbvAmt > 0) {
                $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
                $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
                if (round($dpbvNairaEquivalent, 2) < $dpbvAmt) {
                    return back()->withErrors(['split_dpbv_amount' => 'Insufficient DPBV balance.'])->withInput();
                }
                $blocked = $this->dpbvBlockedProductNames($drafts);
                if ($blocked !== []) {
                    return back()->with('error', 'These products cannot use DPBV: '.implode(', ', $blocked).'.');
                }
            }

            $posMachine = $request->filled('pos_machine_id') ? PosMachine::find($request->input('pos_machine_id')) : null;
            $bankAccount = $request->filled('bank_account_id') ? Bank::find($request->input('bank_account_id')) : null;
            $paymentBreakdown = [
                'wallet' => $walletAmt,
                'kd_credit' => $kdAmt,
                'dpbv' => $dpbvAmt,
                'cash' => $cashAmt,
                'cheque' => $chequeAmt,
                'pos' => $posAmt,
                'bank' => $bankAmt,
                'coupon_code' => $coupon?->code,
                'discount' => $discountAmount,
                'gross_total' => $grossTotal,
                'pos_machine' => $posMachine ? trim(($posMachine->bank_name ?: 'POS').($posMachine->account_number ? ' • '.$posMachine->account_number : '')) : null,
                'bank_account' => $bankAccount ? trim(($bankAccount->name ?: 'Bank').($bankAccount->account_number ? ' • '.$bankAccount->account_number : '')) : null,
                'total' => $totalAmount,
                'order_group_id' => $orderGroup->id,
            ];
            $paymentCompleted = $totalAmount <= 0 || ($walletAmt + $kdAmt + $dpbvAmt + $cashAmt + $chequeAmt + $posAmt + $bankAmt) >= $totalAmount;
        } elseif ($paymentMethod === 'wallet') {
            if ($totalAmount > 0 && (float) ($walletOwner->wallet_balance ?? 0) < $totalAmount) {
                return back()->with('error', 'Insufficient wallet balance.');
            }
            $walletAmt = $totalAmount;
            $paymentCompleted = true;
        } elseif ($paymentMethod === 'kd_credit') {
            if ($kdId === '') {
                return back()->withErrors(['kd_id' => 'Credit code (KD NO) is required to pay with KD Credit.'])->withInput();
            }
            $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
            if (! $kdRegistration) {
                return back()->with('error', 'KD Registration not found for that credit code.');
            }
            $kdCreditBalance = (float) $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
            if ($totalAmount > 0 && $kdCreditBalance < $totalAmount) {
                return back()->with('error', 'Insufficient KD credit balance.');
            }
            $kdAmt = $totalAmount;
            $paymentCompleted = true;
        } elseif ($paymentMethod === 'dpbv') {
            $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
            $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
            if ($totalAmount > 0 && round($dpbvNairaEquivalent, 2) < $totalAmount) {
                return back()->with('error', 'Insufficient DPBV balance.');
            }
            $blocked = $this->dpbvBlockedProductNames($drafts);
            if ($blocked !== []) {
                return back()->with('error', 'These products cannot use DPBV: '.implode(', ', $blocked).'.');
            }
            $dpbvAmt = $totalAmount;
            $paymentCompleted = true;
        } elseif ($paymentMethod === Order::PAYMENT_PAY_ON_DELIVERY) {
            $paymentCompleted = false;
        }

        [$roleName, $stockOwner, $stockUserId, $isHeadquarters, $branchUserId] = $this->resolveStockContext($user);

        foreach ($drafts as $order) {
            foreach ($order->items as $item) {
                $product = Product::where('item_code', $item->item_code)->first();
                if (! $product) {
                    continue;
                }
                if ($isHeadquarters) {
                    $avail = HeadquartersStock::getQuantity($stockOwner->id, $product->id);
                    if ($avail < $item->quantity) {
                        return back()->with('error', "Insufficient Headquarters stock for {$item->product_name} in {$order->invoice_number}. Available: {$avail}.");
                    }
                } elseif ($stockUserId) {
                    $avail = $this->getStockForUser($stockUserId, $roleName, $product->id);
                    if ($avail < $item->quantity) {
                        return back()->with('error', "Insufficient stock for {$item->product_name} in {$order->invoice_number}. Available: {$avail}.");
                    }
                } elseif ($product->stock < $item->quantity) {
                    return back()->with('error', "Insufficient stock for {$item->product_name} in {$order->invoice_number}. Available: {$product->stock}.");
                }
            }
        }

        $status = $paymentCompleted ? Order::STATUS_PAID : Order::STATUS_PENDING;

        DB::transaction(function () use (
            $user,
            $walletOwner,
            $drafts,
            $orderGroup,
            $paymentMethod,
            $paymentBreakdown,
            $walletAmt,
            $kdAmt,
            $dpbvAmt,
            $kdId,
            $customerName,
            $grossTotal,
            $totalAmount,
            $discountAmount,
            $coupon,
            $status,
            $paymentCompleted,
            $branchUserId,
            $isHeadquarters,
            $stockOwner,
            $stockUserId,
            $roleName
        ) {
            $remainingDiscount = $discountAmount;
            $draftCount = $drafts->count();
            foreach ($drafts as $index => $order) {
                $orderDiscount = 0.0;
                if ($discountAmount > 0 && $grossTotal > 0) {
                    if ($index === $draftCount - 1) {
                        $orderDiscount = round($remainingDiscount, 2);
                    } else {
                        $orderDiscount = round(($order->subtotal / $grossTotal) * $discountAmount, 2);
                        $remainingDiscount = round($remainingDiscount - $orderDiscount, 2);
                    }
                }

                $order->update([
                    'payment_method' => $paymentMethod,
                    'payment_breakdown' => $paymentBreakdown,
                    'status' => $status,
                    'branch_user_id' => $branchUserId ?? $order->branch_user_id,
                    'kd_id' => $order->kd_id ?: ($kdId !== '' ? $kdId : null),
                    'customer_name' => $order->customer_name ?: ($customerName !== '' ? $customerName : null),
                    'coupon_id' => $coupon?->id,
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $orderDiscount,
                ]);

                if ($paymentCompleted) {
                    $order->load('items');
                    $this->deductStockForCompletedPayment($order->items, $isHeadquarters, $stockUserId, $roleName, (int) $stockOwner->id);
                    $order->update(['stock_deducted_at' => now()]);
                }
            }

            if ($coupon) {
                $coupon->increment('used_count');
            }

            // Apply wallet / KD / DPBV once for the whole group total.
            if ($walletAmt > 0 && $walletOwner) {
                $walletOwner->decrement('wallet_balance', $walletAmt);
                $balanceAfter = (float) $walletOwner->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $walletOwner->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $walletAmt,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order group #'.$orderGroup->id,
                ]);
            }

            if ($kdAmt > 0 && $kdId !== '') {
                $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
                if ($kdRegistration) {
                    $currentBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                    KdRegistrationCredit::create([
                        'kd_registration_id' => $kdRegistration->id,
                        'type' => KdRegistrationCredit::TYPE_DEBIT,
                        'amount' => $kdAmt,
                        'balance_after' => $currentBalance - $kdAmt,
                        'reference' => 'Order group #'.$orderGroup->id,
                        'notes' => 'Payment for order group',
                        'created_by_user_id' => $user->id,
                    ]);
                }
            }

            if ($dpbvAmt > 0) {
                $dpbvToDeduct = $dpbvAmt / 990 / 0.95;
                DpbvCollection::create([
                    'no' => null,
                    'code' => $kdId !== '' ? $kdId : 'USED',
                    'name' => $customerName !== '' ? $customerName : $user->name,
                    'record_date' => now(),
                    'sc' => 'GROUP',
                    'dpbv' => -$dpbvToDeduct,
                    'user_id' => $user->id,
                ]);
            }

            $orderGroup->update([
                'status' => OrderGroup::STATUS_PAID,
                'payment_method' => $paymentMethod,
                'payment_breakdown' => $paymentBreakdown ?: [
                    'gross_total' => $grossTotal,
                    'discount' => $discountAmount,
                    'coupon_code' => $coupon?->code,
                    'total' => $totalAmount,
                    'wallet' => $walletAmt,
                    'kd_credit' => $kdAmt,
                    'dpbv' => $dpbvAmt,
                ],
                'total_amount' => $totalAmount,
                'completed_at' => now(),
            ]);

            foreach ($drafts as $order) {
                $order->load(['user', 'items']);
                try {
                    Mail::to($user->email)->send(new OrderConfirmationMail($order));
                } catch (\Throwable $e) {
                    \Log::warning('Order confirmation email failed: '.$e->getMessage());
                }
            }
        });

        $request->session()->forget(['order_group_id', 'coupon_code']);
        $request->session()->put('placed_order_ids', $drafts->pluck('id')->toArray());

        $first = $drafts->first();
        $msg = $paymentCompleted
            ? 'Group paid successfully. '.$drafts->count().' order(s) for ₦'.number_format($totalAmount, 0).'.'
            : 'Group orders placed as pay on delivery. '.$drafts->count().' order(s).';

        return redirect()->route('orders.receipt', $first)->with('success', $msg);
    }

    private function authorizeGroup(Request $request, OrderGroup $orderGroup): void
    {
        if ((int) $orderGroup->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }

    /**
     * Drafts the user can move into this group: ungrouped, or linked to another/closed group.
     */
    private function attachableDraftsQuery($user, OrderGroup $orderGroup)
    {
        $userIds = $this->draftOwnerUserIds($user);

        return Order::query()
            ->whereIn('user_id', $userIds)
            ->where('status', Order::STATUS_DRAFT)
            ->where(function ($q) use ($orderGroup) {
                $q->whereNull('order_group_id')
                    ->orWhere('order_group_id', '!=', $orderGroup->id);
            });
    }

    /**
     * @return list<int>
     */
    private function draftOwnerUserIds($user): array
    {
        $ids = [(int) $user->id];

        // Include drafts created by cashiers/distributors under this account.
        $childIds = User::query()
            ->where('created_by_user_id', $user->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        // If this user is a cashier/distributor, also include their parent's drafts.
        if ($user->created_by_user_id) {
            $ids[] = (int) $user->created_by_user_id;
        }

        return array_values(array_unique(array_merge($ids, $childIds)));
    }

    /**
     * @return array<string, mixed>
     */
    private function groupPaymentViewData(Request $request, OrderGroup $orderGroup): array
    {
        $drafts = $orderGroup->orders->where('status', Order::STATUS_DRAFT)->values();
        if ($drafts->isEmpty() && $orderGroup->relationLoaded('orders') === false) {
            $drafts = $orderGroup->orders()->where('status', Order::STATUS_DRAFT)->with('items')->get();
        }
        $draftTotal = (float) $drafts->sum('subtotal');

        $coupon = null;
        $discountAmount = 0.0;
        $couponCode = $request->session()->get('coupon_code');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isValid()) {
                $discountAmount = round(((float) $coupon->discount_percentage / 100) * $draftTotal, 2);
            } else {
                $request->session()->forget('coupon_code');
                $coupon = null;
                $couponCode = null;
            }
        }
        $amountDue = max(0, round($draftTotal - $discountAmount, 2));

        $user = $request->user();
        $user->load(['role', 'createdBy.role']);
        $walletOwner = $user->walletOwnerForShopping();
        $walletBalance = (float) ($walletOwner->wallet_balance ?? 0);

        $totalDpbv = (float) $this->effectiveDpbvQuery($user)->sum('dpbv');
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;

        $kdId = trim((string) ($request->old('kd_id') ?: $orderGroup->kd_id ?: $request->session()->get('kd_id', '')));
        // Prefer a KD from draft orders in the group if session has none.
        if ($kdId === '') {
            foreach ($drafts as $draft) {
                if (trim((string) $draft->kd_id) !== '') {
                    $kdId = trim((string) $draft->kd_id);
                    break;
                }
            }
        }
        $kdCreditBalance = 0;
        if ($kdId !== '') {
            $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
            if ($kdRegistration) {
                $kdCreditBalance = (float) $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
            }
        }

        $paymentOptions = $this->checkoutPaymentOptions($user);

        return [
            'drafts' => $drafts,
            'draftTotal' => $draftTotal,
            'coupon' => $coupon,
            'discountAmount' => $discountAmount,
            'amountDue' => $amountDue,
            'walletBalance' => $walletBalance,
            'canPayWithWallet' => $walletBalance >= $amountDue && $amountDue > 0,
            'totalDpbv' => $totalDpbv,
            'dpbvNairaEquivalent' => $dpbvNairaEquivalent,
            'canPayWithDpbv' => round($dpbvNairaEquivalent, 2) >= round($amountDue, 2) && $amountDue > 0,
            'kdId' => $kdId,
            'customerName' => trim((string) ($orderGroup->customer_name ?: $request->session()->get('customer_name', ''))),
            'kdCreditBalance' => $kdCreditBalance,
            'canPayWithCredit' => $kdCreditBalance >= $amountDue && $amountDue > 0,
            'posMachines' => $paymentOptions['posMachines'],
            'banks' => $paymentOptions['banks'],
        ];
    }

    private function activateSession(Request $request, OrderGroup $group): void
    {
        $request->session()->put('order_group_id', $group->id);
        // Do not set kd_id/customer_name here — each checkout transaction has its own KEDI session.
    }

    private function normalizeKdNo(string $kdNo): string
    {
        $kdNo = strtoupper(trim($kdNo));

        return preg_replace('/\s+/', '', $kdNo) ?? $kdNo;
    }

    private function registerKdCustomer(string $kdId, string $customerName, int $userId): void
    {
        $existing = KdCustomer::whereRaw("UPPER(REPLACE(kd_no, ' ', '')) = ?", [$kdId])->first();
        if ($existing) {
            if (trim((string) $existing->customer_name) === '') {
                $existing->update(['customer_name' => $customerName]);
            }

            return;
        }

        KdCustomer::create([
            'kd_no' => $kdId,
            'customer_name' => $customerName,
            'user_id' => $userId,
        ]);
    }

    private function resolveStockContext(User $user): array
    {
        $roleName = $user->role?->name ?? '';
        $stockOwner = $user;
        if (in_array($roleName, ['cashier', 'distributor'], true) && $user->createdBy && $user->createdBy->role) {
            $ownerRole = $user->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                $stockOwner = $user->createdBy;
                $roleName = $ownerRole;
            }
        }

        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex'], true) ? (int) $stockOwner->id : null;
        $isHeadquarters = $roleName === 'headquarters';
        $branchUserId = ($isHeadquarters || $stockUserId) ? (int) $stockOwner->id : null;

        return [$roleName, $stockOwner, $stockUserId, $isHeadquarters, $branchUserId];
    }

    /** @param  \Illuminate\Support\Collection<int, Order>  $drafts */
    private function dpbvBlockedProductNames($drafts): array
    {
        $blocked = [];
        foreach ($drafts as $order) {
            foreach ($order->items as $item) {
                $product = Product::where('item_code', $item->item_code)->first();
                if ($product && ! ($product->can_use_dpbv ?? true)) {
                    $blocked[] = $product->name;
                }
            }
        }

        return array_values(array_unique($blocked));
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

    private function checkoutPaymentOptions(?User $user): array
    {
        $posMachines = PosMachine::query()->orderBy('bank_name')->orderBy('account_name')->get();
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

    private function deductStockForCompletedPayment(iterable $items, bool $isHeadquarters, ?int $stockUserId, string $roleName, int $stockOwnerId): void
    {
        foreach ($items as $item) {
            $product = Product::where('item_code', $item->item_code)->first();
            if (! $product) {
                continue;
            }
            $qty = (int) $item->quantity;
            if ($isHeadquarters) {
                HeadquartersStock::decrementStock($stockOwnerId, $product->id, $qty);
            } elseif ($stockUserId) {
                if ($roleName === 'branch') {
                    BranchStock::decrementStock($stockUserId, $product->id, $qty);
                } elseif ($roleName === 'service_center') {
                    ServiceCenterStock::decrementStock($stockUserId, $product->id, $qty);
                } elseif ($roleName === 'annex') {
                    AnnexStock::decrementStock($stockUserId, $product->id, $qty);
                }
            } else {
                $product->decrement('stock', $qty);
            }
        }
    }
}
