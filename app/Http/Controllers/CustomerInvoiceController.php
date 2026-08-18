<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\DpbvCollection;
use App\Models\Bank;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KediCreditTransaction;
use App\Models\PosMachine;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerInvoiceController extends Controller
{
    private const PAYMENT_METHODS = [
        'wallet',
        'pay_on_delivery',
        'dpbv',
        'kd_credit',
        'cash',
        'transfer',
    ];

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

    private function effectiveDpbvQuery(User $user)
    {
        $query = DpbvCollection::query()->where('user_id', $user->id);

        $serviceCenterCode = trim((string) ($user->service_center_code ?? ''));
        if (($user->role?->name ?? null) === Role::SERVICE_CENTER && $serviceCenterCode !== '') {
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

    private function assertOwner(Request $request, Invoice $invoice): void
    {
        if ((int) $invoice->user_id !== (int) $request->user()->id) {
            abort(404);
        }
    }

    /**
     * Merge manual invoice items that have the same item_name (case-insensitive).
     *
     * Keeps the first item's description/unit, sums quantities and line totals.
     * If unit_price differs across merged rows, unit_price becomes a weighted average.
     *
     * @param  array<int, array{item_name:string,description:?string,quantity:float,unit:?string,unit_price:float,line_total:float}>  $items
     * @return array<int, array{item_name:string,description:?string,quantity:float,unit:?string,unit_price:float,line_total:float}>
     */
    private function mergeItemsByName(array $items): array
    {
        $merged = [];
        $order = [];

        foreach ($items as $row) {
            $name = trim((string) ($row['item_name'] ?? ''));
            if ($name === '') {
                continue;
            }
            $key = mb_strtolower($name);

            if (! array_key_exists($key, $merged)) {
                $merged[$key] = [
                    'item_name' => $name,
                    'description' => $row['description'] ?? null,
                    'quantity' => (float) ($row['quantity'] ?? 0),
                    'unit' => $row['unit'] ?? null,
                    'unit_price' => (float) ($row['unit_price'] ?? 0),
                    'line_total' => (float) ($row['line_total'] ?? 0),
                ];
                $order[] = $key;
                continue;
            }

            $existing = $merged[$key];
            $existingQty = (float) ($existing['quantity'] ?? 0);
            $existingLineTotal = (float) ($existing['line_total'] ?? 0);

            $addQty = (float) ($row['quantity'] ?? 0);
            $addLineTotal = (float) ($row['line_total'] ?? 0);

            $newQty = $existingQty + $addQty;
            $newLineTotal = $existingLineTotal + $addLineTotal;

            // keep first non-empty description/unit if present
            if (($existing['description'] ?? null) === null || trim((string) ($existing['description'] ?? '')) === '') {
                $existing['description'] = $row['description'] ?? null;
            }
            if (($existing['unit'] ?? null) === null || trim((string) ($existing['unit'] ?? '')) === '') {
                $existing['unit'] = $row['unit'] ?? null;
            }

            $existing['quantity'] = $newQty;
            $existing['line_total'] = $newLineTotal;
            $existing['unit_price'] = $newQty > 0 ? ($newLineTotal / $newQty) : 0.0;

            $merged[$key] = $existing;
        }

        $out = [];
        foreach ($order as $key) {
            if (! isset($merged[$key])) {
                continue;
            }
            $out[] = $merged[$key];
        }
        return $out;
    }

    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string', 'max:100'],
        ]);

        $code = trim((string) $request->input('code'));
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon || ! $coupon->isValid()) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid coupon code.',
            ]);
        }

        return response()->json([
            'valid' => true,
            'discount_percentage' => (float) $coupon->discount_percentage,
        ]);
    }

    public function index(Request $request)
    {
        $query = Invoice::where('user_id', $request->user()->id);

        $status = trim((string) $request->query('status', ''));
        if ($status !== '') {
            $allowed = ['draft', 'sent', 'paid', 'overdue', 'cancelled'];
            if (in_array($status, $allowed, true)) {
                $query->where('status', $status);
            }
        }

        $invoices = $query
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $cartCount = array_sum($request->session()->get('cart', []));
        $statusCounts = Invoice::where('user_id', $request->user()->id)
            ->whereIn('status', ['draft', 'sent', 'paid'])
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return view('invoices.index', [
            'invoices' => $invoices,
            'cartCount' => $cartCount,
            'status' => $status,
            'statusCounts' => $statusCounts,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $cartCount = array_sum($request->session()->get('cart', []));
        $posMachines = PosMachine::query()
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        // Bank accounts (active) — same HQ chain logic as wallet top-up
        $banksQuery = Bank::where('is_active', true);
        $hqId = null;
        $forBanks = $user->bankContextUser();
        if ($forBanks->role?->name === 'headquarters') {
            $hqId = (int) $forBanks->id;
        } elseif ($forBanks->role?->name === 'branch') {
            $hqId = (int) $forBanks->created_by_user_id;
        } elseif ($forBanks->role?->name === 'service_center' && $forBanks->created_by_user_id) {
            $branch = \App\Models\User::find($forBanks->created_by_user_id);
            $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
        } elseif (in_array($forBanks->role?->name, ['annex', 'dispatch', 'accountant'], true) && $forBanks->created_by_user_id) {
            $creator = \App\Models\User::with('role')->find($forBanks->created_by_user_id);
            if ($creator && $creator->role?->name === 'service_center' && $creator->created_by_user_id) {
                $branch = \App\Models\User::find($creator->created_by_user_id);
                $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
            } elseif ($creator && $creator->role?->name === 'branch') {
                $hqId = (int) $creator->created_by_user_id;
            }
        }
        if ($hqId) {
            $banksQuery->where('headquarters_user_id', $hqId);
        }
        $banks = $banksQuery->orderBy('name')->get();
        $manualProducts = $products->map(function (Product $p) use ($user) {
            return [
                'name' => (string) $p->display_name,
                'unit' => (string) ($p->pack_size ?? 'pcs'),
                'price' => (float) $p->getPriceForUser($user),
                'pv' => (float) ($p->pv ?? 0),
                'bv' => (float) ($p->bv ?? 0),
            ];
        })->values()->all();

        $prefillQuantities = [];
        $addedItems = $request->session()->get('added_items', []);
        if (! empty($addedItems)) {
            foreach ($addedItems as $row) {
                $product = Product::where('item_code', $row['item_code'] ?? '')->where('is_active', true)->first();
                if ($product) {
                    $prefillQuantities[$product->id] = ($prefillQuantities[$product->id] ?? 0) + (int) ($row['quantity'] ?? 0);
                }
            }
            $request->session()->forget('added_items');
        }

        return view('invoices.create', [
            'user' => $user,
            'products' => $products,
            'manualProducts' => $manualProducts,
            'cartCount' => $cartCount,
            'prefillQuantities' => $prefillQuantities,
            'posMachines' => $posMachines,
            'banks' => $banks,
            'statusCounts' => Invoice::where('user_id', $user->id)
                ->whereIn('status', ['draft', 'sent', 'paid'])
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $useProducts = $request->boolean('use_product_quantities');
        $splitPayment = $request->boolean('split_payment');
        $useScReferral = $request->boolean('use_sc_referral');

        if ($useProducts) {
            $data = $request->validate([
                'customer_name' => ['nullable', 'string', 'max:255'],
                'buyer_name' => ['nullable', 'string', 'max:255'],
                'sc_referral_code' => ['nullable', 'string', 'max:100'],
                'coupon_code' => ['nullable', 'string', 'max:100'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:50'],
                'customer_address' => ['nullable', 'string'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['nullable', 'string', Rule::in(self::PAYMENT_METHODS)],
                'pos_amount_paid' => ['nullable', 'numeric', 'min:0'],
                'bank_amount_paid' => ['nullable', 'numeric', 'min:0'],
                'split_payment' => ['nullable'],
                'split_wallet_amount' => ['nullable', 'numeric', 'min:0'],
                'split_kd_credit_amount' => ['nullable', 'numeric', 'min:0'],
                'split_cash_amount' => ['nullable', 'numeric', 'min:0'],
                'split_transfer_amount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
                'notes' => ['nullable', 'string'],
                'use_sc_referral' => ['nullable'],
                'use_product_quantities' => ['nullable'],
                'product_quantities' => ['required', 'array'],
                'product_quantities.*' => ['numeric', 'min:0'],
            ]);

            $productQuantities = array_filter($data['product_quantities'] ?? [], fn ($q) => (float) $q > 0);
            if (empty($productQuantities)) {
                return redirect()->back()->withInput()->withErrors(['product_quantities' => 'Enter quantity for at least one product.']);
            }

            $productIds = array_keys($productQuantities);
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $items = [];
            foreach ($productQuantities as $productId => $qty) {
                $product = $products->get($productId);
                if (!$product) {
                    continue;
                }
                $qty = (float) $qty;
                $unitPrice = $product->getPriceForUser($user);
                $items[] = [
                    'item_name' => $product->display_name,
                    'description' => null,
                    'quantity' => $qty,
                    'unit' => $product->pack_size ?? 'pcs',
                    'unit_price' => $unitPrice,
                    'line_total' => $qty * $unitPrice,
                ];
            }
            if (empty($items)) {
                return redirect()->back()->withInput()->withErrors(['product_quantities' => 'Enter quantity for at least one product.']);
            }
        } else {
            $data = $request->validate([
                'customer_name' => ['nullable', 'string', 'max:255'],
                'buyer_name' => ['nullable', 'string', 'max:255'],
                'sc_referral_code' => ['nullable', 'string', 'max:100'],
                'coupon_code' => ['nullable', 'string', 'max:100'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:50'],
                'customer_address' => ['nullable', 'string'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'payment_method' => ['nullable', 'string', Rule::in(self::PAYMENT_METHODS)],
                'pos_amount_paid' => ['nullable', 'numeric', 'min:0'],
                'bank_amount_paid' => ['nullable', 'numeric', 'min:0'],
                'split_payment' => ['nullable'],
                'split_wallet_amount' => ['nullable', 'numeric', 'min:0'],
                'split_kd_credit_amount' => ['nullable', 'numeric', 'min:0'],
                'split_cash_amount' => ['nullable', 'numeric', 'min:0'],
                'split_transfer_amount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
                'notes' => ['nullable', 'string'],
                'use_sc_referral' => ['nullable'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.item_name' => ['nullable', 'string', 'max:255'],
                'items.*.description' => ['nullable', 'string'],
                'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
                'items.*.unit' => ['nullable', 'string', 'max:50'],
                'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
            ]);

            $items = [];
            foreach ($data['items'] as $item) {
                $name = trim((string) ($item['item_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $items[] = [
                    'item_name' => $name,
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                ];
            }
            if (empty($items)) {
                return redirect()->back()->withInput()->withErrors(['items' => 'Enter at least one item.']);
            }
            $items = $this->mergeItemsByName($items);
        }

        $buyerName = trim((string) ($data['buyer_name'] ?? ''));
        if ($buyerName !== '') {
            $data['customer_name'] = $buyerName;
        }

        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data['user_id'] = $user->id;

        $subtotal = array_sum(array_column($items, 'line_total'));
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);

        $couponDiscountAmount = 0.0;
        $couponCode = trim((string) ($data['coupon_code'] ?? ''));
        $couponIdToConsume = null;
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (! $coupon || ! $coupon->isValid()) {
                return redirect()->back()->withInput()->withErrors(['coupon_code' => 'Invalid coupon code.']);
            }
            $couponIdToConsume = (int) $coupon->id;
            $couponDiscountAmount = ((float) $coupon->discount_percentage / 100.0) * (float) $subtotal;
        } else {
            $couponCode = null;
        }

        $totalDiscount = $discount + $couponDiscountAmount;
        $total = $subtotal + $tax - $totalDiscount;

        $status = (string) ($data['status'] ?? '');
        $shouldDeduct = ($status === 'paid');

        $paymentMethod = $data['payment_method'] ?? null;
        $scReferralCode = $data['sc_referral_code'] ?? null;

        $serviceCenter = null;
        $kediCreditOwner = null;
        if ($shouldDeduct && ($paymentMethod === 'wallet' || $paymentMethod === 'dpbv' || $paymentMethod === 'kd_credit' || $splitPayment)) {
            $serviceCenter = $this->resolveServiceCenterByCode($scReferralCode);
            $kediCreditOwner = $serviceCenter ?: $user;

            if (($paymentMethod === 'wallet' || $paymentMethod === 'dpbv') && ! $serviceCenter) {
                return redirect()->back()->withInput()->withErrors([
                    'sc_referral_code' => 'Valid Service Center Referral Code is required to pay with '.($paymentMethod === 'dpbv' ? 'DPBV' : 'wallet').'.',
                ]);
            }

            if ($paymentMethod === 'wallet') {
                if (((float) ($serviceCenter->wallet_balance ?? 0)) < (float) $total) {
                    return redirect()->back()->withInput()->withErrors([
                        'payment_method' => 'Insufficient Service Center wallet balance to pay this invoice.',
                    ]);
                }
            }

            if ($paymentMethod === 'dpbv') {
                $totalDpbv = (float) $this->effectiveDpbvQuery($serviceCenter)->sum('dpbv');
                $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
                if (round($dpbvNairaEquivalent, 2) < round((float) $total, 2)) {
                    return redirect()->back()->withInput()->withErrors([
                        'payment_method' => 'Insufficient Service Center DPBV balance to pay this invoice.',
                    ]);
                }
            }

            if ($paymentMethod === 'kd_credit') {
                if (((float) ($kediCreditOwner->kedi_credit_balance ?? 0)) < (float) $total) {
                    return redirect()->back()->withInput()->withErrors([
                        'payment_method' => 'Insufficient Kedi Credit balance to pay this invoice.',
                    ]);
                }
            }
        }

        // When user chose to use Service Center details, apply them to customer fields (where possible)
        if ($useScReferral) {
            $serviceCenter = $serviceCenter ?: $this->resolveServiceCenterByCode($scReferralCode);
            if ($serviceCenter) {
                $data['customer_name'] = $serviceCenter->name;
                $data['customer_email'] = $serviceCenter->email;
                $data['customer_phone'] = $serviceCenter->phone ?? null;
                $data['customer_address'] = $serviceCenter->address ?? null;
            }
        }

        $paymentBreakdown = null;
        if ($shouldDeduct && $splitPayment) {
            $walletAmt = (float) ($data['split_wallet_amount'] ?? 0);
            $kdAmt = (float) ($data['split_kd_credit_amount'] ?? 0);
            $cashAmt = (float) ($data['split_cash_amount'] ?? 0);
            $transferAmt = (float) ($data['split_transfer_amount'] ?? 0);
            $posAmt = (float) ($data['pos_amount_paid'] ?? 0);
            $bankAmt = (float) ($data['bank_amount_paid'] ?? 0);

            $sum = $walletAmt + $kdAmt + $cashAmt + $transferAmt + $posAmt + $bankAmt;
            if (round($sum, 2) <= 0) {
                return redirect()->back()->withInput()->withErrors([
                    'split_payment' => 'Enter at least one split payment amount.',
                ]);
            }
            if (round($sum, 2) !== round((float) $total, 2)) {
                return redirect()->back()->withInput()->withErrors([
                    'split_payment' => 'Split payment amounts must add up to the invoice total (₦'.number_format((float) $total, 2).').',
                ]);
            }

            if ($walletAmt > 0 && ! $serviceCenter) {
                return redirect()->back()->withInput()->withErrors([
                    'sc_referral_code' => 'Valid Service Center Referral Code is required to pay any amount from wallet.',
                ]);
            }

            if ($walletAmt > 0 && ((float) ($serviceCenter->wallet_balance ?? 0)) < (float) $walletAmt) {
                return redirect()->back()->withInput()->withErrors([
                    'split_wallet_amount' => 'Insufficient Service Center wallet balance for the wallet amount entered.',
                ]);
            }

            if ($kdAmt > 0 && ((float) ($kediCreditOwner->kedi_credit_balance ?? 0)) < (float) $kdAmt) {
                return redirect()->back()->withInput()->withErrors([
                    'split_kd_credit_amount' => 'Insufficient Kedi Credit balance for the KD Credit amount entered.',
                ]);
            }

            $paymentMethod = 'split';
            $paymentBreakdown = [
                'wallet' => round($walletAmt, 2),
                'kd_credit' => round($kdAmt, 2),
                'cash' => round($cashAmt, 2),
                'transfer' => round($transferAmt, 2),
                'pos' => round($posAmt, 2),
                'bank' => round($bankAmt, 2),
                'total' => round((float) $total, 2),
            ];
        }

        $invoice = null;
        DB::transaction(function () use ($data, $items, $subtotal, $tax, $discount, $couponCode, $couponIdToConsume, $couponDiscountAmount, $total, $user, $paymentMethod, $paymentBreakdown, $serviceCenter, $kediCreditOwner, $splitPayment, $shouldDeduct, &$invoice) {
            if ($shouldDeduct && $couponCode && $couponIdToConsume) {
                // Consume coupon (single-use) safely inside transaction
                $locked = Coupon::where('id', $couponIdToConsume)->lockForUpdate()->first();
                if (! $locked || ! $locked->isValid()) {
                    abort(422, 'Coupon is no longer valid.');
                }
                $locked->increment('used_count');
            }

            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'user_id' => $data['user_id'],
                'branch_user_id' => $serviceCenter?->id,
                'customer_name' => $data['customer_name'] ?? $user->name ?? null,
                'sc_referral_code' => $data['sc_referral_code'] ?? null,
                'coupon_code' => $couponCode,
                'customer_email' => $data['customer_email'] ?? $user->email ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'coupon_discount_amount' => $couponDiscountAmount,
                'total' => $total,
                'status' => $data['status'],
                'payment_method' => $paymentMethod,
                'pos_amount_paid' => (float) ($data['pos_amount_paid'] ?? 0) > 0 ? (float) $data['pos_amount_paid'] : null,
                'payment_breakdown' => $paymentBreakdown,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $index,
                ]);
            }

            // Deduct from Service Center wallet when paying with wallet
            if ($shouldDeduct && ($paymentMethod === 'wallet' || $splitPayment) && $serviceCenter) {
                $debitAmount = (float) ($splitPayment ? ((float) ($paymentBreakdown['wallet'] ?? 0)) : (float) $total);
                if ($debitAmount <= 0) {
                    // no-op for split when wallet portion is 0
                } else {
                $serviceCenter->decrement('wallet_balance', $debitAmount);
                $balanceAfter = (float) $serviceCenter->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $serviceCenter->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $debitAmount,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Invoice #'.$invoice->invoice_number,
                    'status' => WalletTransaction::STATUS_ACCEPTED,
                    'approved_at' => now(),
                ]);
                }
            }

            // Deduct from Service Center DPBV when paying with DPBV
            if ($shouldDeduct && $paymentMethod === 'dpbv' && $serviceCenter) {
                $amountToDeduct = (float) $total;
                $dpbvToDeduct = $amountToDeduct / 990 / 0.95;

                DpbvCollection::create([
                    'no' => null,
                    'code' => $invoice->invoice_number,
                    'name' => $serviceCenter->name,
                    'record_date' => now(),
                    'sc' => 'INVOICE',
                    'dpbv' => -$dpbvToDeduct,
                    'user_id' => $serviceCenter->id,
                ]);
            }

            // Deduct from Kedi Credit when paying with KD Credit
            if ($shouldDeduct && ($paymentMethod === 'kd_credit' || $splitPayment) && $kediCreditOwner) {
                $debitAmount = (float) ($splitPayment ? ((float) ($paymentBreakdown['kd_credit'] ?? 0)) : (float) $total);
                if ($debitAmount <= 0) {
                    // no-op for split when kd_credit portion is 0
                } else {
                $kediCreditOwner->decrement('kedi_credit_balance', $debitAmount);
                $balanceAfter = (float) $kediCreditOwner->fresh()->kedi_credit_balance;

                KediCreditTransaction::create([
                    'user_id' => $kediCreditOwner->id,
                    'type' => KediCreditTransaction::TYPE_DEBIT,
                    'amount' => $debitAmount,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Invoice #'.$invoice->invoice_number,
                    'notes' => $serviceCenter ? 'Debited from Service Center Kedi Credit via referral code.' : 'Debited from customer Kedi Credit.',
                    'created_by_user_id' => $user->id,
                ]);
                }
            }
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.')
            ->with('created_invoice_id', $invoice->id);
    }

    protected function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::orderByDesc('id')->first();
        $nextNum = $lastInvoice ? ((int) preg_replace('/[^0-9]/', '', $lastInvoice->invoice_number)) + 1 : 1;
        return 'INV-' . str_pad((string) $nextNum, 6, '0', STR_PAD_LEFT);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $this->assertOwner($request, $invoice);

        $invoice->load('items', 'user');
        $logoPath = public_path('images/logo.png');
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ]);
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function show(Request $request, Invoice $invoice)
    {
        $this->assertOwner($request, $invoice);

        $invoice->load('items', 'user');
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('invoices.show', [
            'invoice' => $invoice,
            'cartCount' => $cartCount,
        ]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $this->assertOwner($request, $invoice);

        $user = $request->user();
        $invoice->load('items');
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $cartCount = array_sum($request->session()->get('cart', []));
        $posMachines = PosMachine::query()
            ->orderBy('bank_name')
            ->orderBy('account_name')
            ->get();

        // Bank accounts (active) — same HQ chain logic as wallet top-up / create invoice
        $banksQuery = Bank::where('is_active', true);
        $hqId = null;
        $forBanks = $user->bankContextUser();
        if ($forBanks->role?->name === 'headquarters') {
            $hqId = (int) $forBanks->id;
        } elseif ($forBanks->role?->name === 'branch') {
            $hqId = (int) $forBanks->created_by_user_id;
        } elseif ($forBanks->role?->name === 'service_center' && $forBanks->created_by_user_id) {
            $branch = \App\Models\User::find($forBanks->created_by_user_id);
            $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
        } elseif (in_array($forBanks->role?->name, ['annex', 'dispatch', 'accountant'], true) && $forBanks->created_by_user_id) {
            $creator = \App\Models\User::with('role')->find($forBanks->created_by_user_id);
            if ($creator && $creator->role?->name === 'service_center' && $creator->created_by_user_id) {
                $branch = \App\Models\User::find($creator->created_by_user_id);
                $hqId = ($branch && $branch->created_by_user_id) ? (int) $branch->created_by_user_id : null;
            } elseif ($creator && $creator->role?->name === 'branch') {
                $hqId = (int) $creator->created_by_user_id;
            }
        }
        if ($hqId) {
            $banksQuery->where('headquarters_user_id', $hqId);
        }
        $banks = $banksQuery->orderBy('name')->get();
        $manualProducts = $products->map(function (Product $p) use ($user) {
            return [
                'name' => (string) $p->display_name,
                'unit' => (string) ($p->pack_size ?? 'pcs'),
                'price' => (float) $p->getPriceForUser($user),
                'pv' => (float) ($p->pv ?? 0),
                'bv' => (float) ($p->bv ?? 0),
            ];
        })->values()->all();

        $prefillQuantities = [];
        foreach ($invoice->items as $item) {
            $product = $products->first(fn ($p) => (string) $p->display_name === (string) $item->item_name);
            if (! $product) {
                continue;
            }
            $prefillQuantities[$product->id] = ($prefillQuantities[$product->id] ?? 0) + (float) $item->quantity;
        }

        return view('invoices.edit', [
            'invoice' => $invoice,
            'user' => $user,
            'products' => $products,
            'manualProducts' => $manualProducts,
            'cartCount' => $cartCount,
            'prefillQuantities' => $prefillQuantities,
            'posMachines' => $posMachines,
            'banks' => $banks,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->assertOwner($request, $invoice);

        $user = $request->user();
        $useProducts = $request->boolean('use_product_quantities');
        $useScReferral = $request->boolean('use_sc_referral');
        $splitPayment = $request->boolean('split_payment');

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'sc_referral_code' => ['nullable', 'string', 'max:100'],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string', 'max:500'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', Rule::in(self::PAYMENT_METHODS)],
            'pos_amount_paid' => ['nullable', 'numeric', 'min:0'],
            'bank_amount_paid' => ['nullable', 'numeric', 'min:0'],
            'split_payment' => ['nullable'],
            'split_wallet_amount' => ['nullable', 'numeric', 'min:0'],
            'split_kd_credit_amount' => ['nullable', 'numeric', 'min:0'],
            'split_cash_amount' => ['nullable', 'numeric', 'min:0'],
            'split_transfer_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'use_sc_referral' => ['nullable'],
            'use_product_quantities' => ['nullable'],
            // optional item inputs; if empty, keep existing items
            'product_quantities' => ['nullable', 'array'],
            'product_quantities.*' => ['numeric', 'min:0'],
            'items' => ['nullable', 'array'],
            'items.*.item_name' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $items = null; // null = keep existing items

        if ($useProducts) {
            $productQuantities = array_filter($data['product_quantities'] ?? [], fn ($q) => (float) $q > 0);
            if (! empty($productQuantities)) {
                $productIds = array_keys($productQuantities);
                $products = Product::whereIn('id', $productIds)->get()->keyBy('id');

                $built = [];
                foreach ($productQuantities as $productId => $qty) {
                    $product = $products->get($productId);
                    if (! $product) {
                        continue;
                    }
                    $qty = (float) $qty;
                    $unitPrice = $product->getPriceForUser($user);
                    $built[] = [
                        'item_name' => $product->display_name,
                        'description' => null,
                        'quantity' => $qty,
                        'unit' => $product->pack_size ?? 'pcs',
                        'unit_price' => $unitPrice,
                        'line_total' => $qty * $unitPrice,
                    ];
                }
                if (! empty($built)) {
                    $items = $built;
                }
            }
        } else {
            $rawItems = $data['items'] ?? [];
            $filtered = array_values(array_filter($rawItems, function ($row) {
                $name = trim((string) ($row['item_name'] ?? ''));
                return $name !== '';
            }));

            if (! empty($filtered)) {
                $built = [];
                foreach ($filtered as $item) {
                    $qty = (float) ($item['quantity'] ?? 0);
                    $price = (float) ($item['unit_price'] ?? 0);
                    $built[] = [
                        'item_name' => (string) $item['item_name'],
                        'description' => $item['description'] ?? null,
                        'quantity' => $qty,
                        'unit' => $item['unit'] ?? null,
                        'unit_price' => $price,
                        'line_total' => $qty * $price,
                    ];
                }
                if (! empty($built)) {
                    $items = $this->mergeItemsByName($built);
                }
            }
        }

        $invoice->loadMissing('items');
        $subtotal = $items === null
            ? (float) $invoice->items->sum('line_total')
            : (float) array_sum(array_column($items, 'line_total'));
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);

        $couponDiscountAmount = 0.0;
        $couponCode = trim((string) ($data['coupon_code'] ?? ''));
        $couponIdToConsume = null;
        if ($couponCode !== '') {
            $coupon = Coupon::where('code', $couponCode)->first();
            if (! $coupon) {
                return redirect()->back()->withInput()->withErrors(['coupon_code' => 'Invalid coupon code.']);
            }

            // If user keeps the same coupon on this invoice, allow it even if it has already been consumed.
            $keepingSameCoupon = (string) ($invoice->coupon_code ?? '') !== '' && (string) $invoice->coupon_code === $couponCode;
            if (! $keepingSameCoupon && ! $coupon->isValid()) {
                return redirect()->back()->withInput()->withErrors(['coupon_code' => 'Invalid coupon code.']);
            }

            $couponIdToConsume = (! $keepingSameCoupon && $coupon->isValid()) ? (int) $coupon->id : null;
            $couponDiscountAmount = ((float) $coupon->discount_percentage / 100.0) * (float) $subtotal;
        } else {
            $couponCode = null;
        }

        $totalDiscount = $discount + $couponDiscountAmount;
        $total = $subtotal + $tax - $totalDiscount;

        if ($useScReferral) {
            $serviceCenter = $this->resolveServiceCenterByCode($data['sc_referral_code'] ?? null);
            if ($serviceCenter) {
                $data['customer_name'] = $serviceCenter->name;
                $data['customer_email'] = $serviceCenter->email;
                $data['customer_phone'] = $serviceCenter->phone ?? null;
                $data['customer_address'] = $serviceCenter->address ?? null;
            }
        }

        $previousCouponCode = (string) ($invoice->coupon_code ?? '');

        DB::transaction(function () use ($invoice, $data, $items, $subtotal, $tax, $discount, $couponCode, $couponIdToConsume, $previousCouponCode, $couponDiscountAmount, $total, $user, $splitPayment) {
            // Adjust coupon usage counts if coupon changed
            if ($previousCouponCode !== (string) ($couponCode ?? '')) {
                if ($previousCouponCode !== '') {
                    $prev = Coupon::where('code', $previousCouponCode)->lockForUpdate()->first();
                    if ($prev && (int) $prev->used_count > 0) {
                        $prev->decrement('used_count');
                    }
                }
                if ($couponCode && $couponIdToConsume) {
                    $locked = Coupon::where('id', $couponIdToConsume)->lockForUpdate()->first();
                    if (! $locked || ! $locked->isValid()) {
                        abort(422, 'Coupon is no longer valid.');
                    }
                    $locked->increment('used_count');
                }
            }

            $invoice->update([
                'customer_name' => $data['customer_name'] ?? $user->name ?? null,
                'sc_referral_code' => $data['sc_referral_code'] ?? null,
                'coupon_code' => $couponCode,
                'customer_email' => $data['customer_email'] ?? $user->email ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'coupon_discount_amount' => $couponDiscountAmount,
                'total' => $total,
                'status' => $data['status'],
                'payment_method' => $data['payment_method'] ?? null,
                'pos_amount_paid' => (float) ($data['pos_amount_paid'] ?? 0) > 0 ? (float) $data['pos_amount_paid'] : null,
                'payment_breakdown' => $splitPayment ? [
                    'wallet' => round((float) ($data['split_wallet_amount'] ?? 0), 2),
                    'kd_credit' => round((float) ($data['split_kd_credit_amount'] ?? 0), 2),
                    'cash' => round((float) ($data['split_cash_amount'] ?? 0), 2),
                    'transfer' => round((float) ($data['split_transfer_amount'] ?? 0), 2),
                    'pos' => round((float) ($data['pos_amount_paid'] ?? 0), 2),
                    'bank' => round((float) ($data['bank_amount_paid'] ?? 0), 2),
                    'total' => round((float) $total, 2),
                ] : null,
                'notes' => $data['notes'] ?? null,
            ]);

            if ($items !== null) {
                $invoice->items()->delete();
                foreach (array_values($items) as $index => $item) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'item_name' => $item['item_name'],
                        'description' => $item['description'] ?? null,
                        'quantity' => $item['quantity'],
                        'unit' => $item['unit'] ?? null,
                        'unit_price' => $item['unit_price'],
                        'line_total' => $item['line_total'],
                        'sort_order' => $index,
                    ]);
                }
            }
        });

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $this->assertOwner($request, $invoice);

        DB::transaction(function () use ($invoice) {
            $invoice->items()->delete();
            $invoice->delete();
        });

        return redirect()
            ->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }
}
