<?php

namespace App\Http\Controllers;

use App\Mail\OrderConfirmationMail;
use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\ServiceCenterStock;
use App\Models\HeadquartersStock;
use App\Models\Guest;
use App\Models\DpbvCollection;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\KdRegistrationCredit;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
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
        $kdId = trim((string) $request->session()->get('kd_id', ''));
        $customerName = trim((string) $request->session()->get('customer_name', ''));
        // Guests can checkout without KD NO/Customer Name; orders stored with null, can be updated later
        $walletBalance = $user ? (float) ($user->wallet_balance ?? 0) : 0;
        $canPayWithWallet = $user->canPayWithWallet($data['cartTotal']);
        
        // Calculate DPBV balance and naira equivalent
        $totalDpbv = $user ? (float) DpbvCollection::where('user_id', $user->id)->sum('dpbv') : 0;
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
        // Check if all products in cart allow DPBV
        $allProductsAllowDpbv = true;
        foreach ($data['cartItems'] as $item) {
            if (!($item->product->can_use_dpbv ?? true)) {
                $allProductsAllowDpbv = false;
                break;
            }
        }
        $canPayWithDpbv = $dpbvNairaEquivalent >= $data['cartTotal'] && $allProductsAllowDpbv;

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

        return view('checkout.show', array_merge($data, [
            'walletBalance' => $walletBalance,
            'canPayWithWallet' => $canPayWithWallet,
            'totalDpbv' => $totalDpbv,
            'dpbvNairaEquivalent' => $dpbvNairaEquivalent,
            'canPayWithDpbv' => $canPayWithDpbv,
            'kdId' => $kdId,
            'customerName' => $customerName,
            'kdCreditBalance' => $kdCreditBalance,
            'canPayWithCredit' => $canPayWithCredit,
        ]));
    }

    public function checkKdCredit(Request $request)
    {
        $request->validate([
            'kd_no' => 'required|string|max:100',
        ]);

        $kdNo = trim($request->input('kd_no'));
        $kdRegistration = KdRegistration::where('kd_no', $kdNo)->first();

        if (!$kdRegistration) {
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
        $rules = [
            'payment_method' => 'required|in:wallet,pay_on_delivery,dpbv,kd_credit',
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

        $request->validate($rules);

        $user = $request->user();
        $paymentMethod = $request->input('payment_method');
        $kdId = trim((string) $request->input('kd_id', ''));
        $customerName = trim((string) $request->input('customer_name', ''));

        if ($paymentMethod === 'wallet' && ! $user->canPayWithWallet($data['cartTotal'])) {
            return back()->with('error', 'Insufficient wallet balance.');
        }

        // Check KD Registration credit balance if paying with credit
        if ($paymentMethod === 'kd_credit') {
            if (!$kdId) {
                return back()->with('error', 'KD NO is required to pay with credit.');
            }
            $kdRegistration = KdRegistration::where('kd_no', $kdId)->first();
            if (!$kdRegistration) {
                return back()->with('error', 'KD Registration not found.');
            }
            $kdCreditBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
            if ($kdCreditBalance < $data['cartTotal']) {
                return back()->with('error', 'Insufficient KD credit balance. You have ₦' . number_format($kdCreditBalance, 2) . ' available.');
            }
        }

        // Check DPBV balance if paying with DPBV
        if ($paymentMethod === 'dpbv') {
            $totalDpbv = (float) DpbvCollection::where('user_id', $user->id)->sum('dpbv');
            $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
            if ($dpbvNairaEquivalent < $data['cartTotal']) {
                return back()->with('error', 'Insufficient DPBV balance. You have ₦' . number_format($dpbvNairaEquivalent, 2) . ' available.');
            }
            
            // Check if all products allow DPBV
            $productsNotAllowed = [];
            foreach ($data['cartItems'] as $item) {
                if (!($item->product->can_use_dpbv ?? true)) {
                    $productsNotAllowed[] = $item->product->name;
                }
            }
            if (!empty($productsNotAllowed)) {
                return back()->with('error', 'The following products cannot be purchased with DPBV: ' . implode(', ', $productsNotAllowed) . '. Please remove them from your cart or use a different payment method.');
            }
        }

        // Guest orders can have null kd_id/customer_name; save to kd_customers when provided
        if ($kdId !== '' && $customerName !== '') {
            $request->session()->put('kd_id', $kdId);
            $request->session()->put('customer_name', $customerName);
        }

        $stockUserId = null; // user whose stock we use (Branch, SC, or Annex)
        $isHeadquarters = $user->role?->name === 'headquarters';
        if ($user->role?->name === 'branch') {
            $stockUserId = (int) $user->id;
        } elseif ($user->role?->name === 'service_center') {
            $stockUserId = (int) $user->id;
        } elseif ($user->role?->name === 'annex') {
            $stockUserId = (int) $user->id;
        }
        $branchUserId = $stockUserId; // keep for order.branch_user_id (used when deducting)

        // Check stock availability before placing order
        foreach ($data['cartItems'] as $item) {
            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($user->id, $item->product->id);
                if ($avail < $item->quantity) {
                    return back()->with('error', "Insufficient Headquarters stock for {$item->product->name}. Available: {$avail}.");
                }
            } elseif ($stockUserId) {
                $avail = $this->getStockForUser($stockUserId, $user->role?->name, $item->product->id);
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
        DB::transaction(function () use ($user, $data, $paymentMethod, $request, $orderKdId, $orderCustomerName, $deliveryType, $shippingAddress, $shippingCity, $shippingState, $shippingPostal, $shippingPhone, $branchUserId, $isHeadquarters, &$order) {
            if (! $orderKdId || ! $orderCustomerName) {
                Guest::firstOrCreate(
                    ['session_id' => $request->session()->getId(), 'user_id' => $user->id],
                    ['session_id' => $request->session()->getId(), 'user_id' => $user->id]
                );
            }
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
                'payment_method' => $paymentMethod === 'dpbv' ? Order::PAYMENT_DPBV : ($paymentMethod === 'kd_credit' ? 'kd_credit' : $paymentMethod),
                'status' => ($paymentMethod === Order::PAYMENT_WALLET || $paymentMethod === Order::PAYMENT_DPBV || $paymentMethod === 'kd_credit') ? Order::STATUS_PAID : Order::STATUS_PENDING,
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'shipping_state' => $shippingState,
                'shipping_postal_code' => $shippingPostal,
                'shipping_phone' => $shippingPhone,
                'coupon_id' => $data['coupon'] ? $data['coupon']->id : null,
                'coupon_code' => $data['coupon'] ? $data['coupon']->code : null,
                'discount_amount' => $data['discountAmount'] ?? 0,
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
                
                // For Headquarters users with paid orders, deduct stock immediately
                if ($isHeadquarters && ($paymentMethod === Order::PAYMENT_WALLET || $paymentMethod === Order::PAYMENT_DPBV)) {
                    HeadquartersStock::decrementStock($user->id, $item->product->id, $item->quantity);
                }
                // For other users, stock will be deducted when order is marked as completed
            }

            if ($paymentMethod === Order::PAYMENT_WALLET) {
                $user->decrement('wallet_balance', $data['cartSubtotal']);
                $balanceAfter = (float) $user->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $data['cartTotal'],
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #' . $order->id,
                ]);
            }

            // Mark coupon as used
            if ($data['coupon']) {
                $data['coupon']->increment('used_count');
            }

            // Deduct KD Registration credit if paying with credit
            if ($paymentMethod === 'kd_credit' && $orderKdId) {
                $kdRegistration = KdRegistration::where('kd_no', $orderKdId)->first();
                if ($kdRegistration) {
                    $currentBalance = $kdRegistration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));
                    $newBalance = $currentBalance - $data['cartTotal'];
                    
                    KdRegistrationCredit::create([
                        'kd_registration_id' => $kdRegistration->id,
                        'type' => KdRegistrationCredit::TYPE_DEBIT,
                        'amount' => $data['cartTotal'],
                        'balance_after' => $newBalance,
                        'reference' => 'Order #' . $order->invoice_number,
                        'notes' => 'Payment for order',
                        'created_by_user_id' => $user->id,
                    ]);
                }
            }

            // Deduct DPBV if paying with DPBV
            if ($paymentMethod === Order::PAYMENT_DPBV) {
                $totalDpbv = (float) DpbvCollection::where('user_id', $user->id)->sum('dpbv');
                $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
                $amountToDeduct = $data['cartTotal'];
                
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
        $request->session()->forget(['kd_id', 'customer_name']);

        $order->load(['user', 'items']);
        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: ' . $e->getMessage());
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

        $roleName = $user->role?->name ?? '';
        $isHeadquarters = ($roleName === 'headquarters');
        $stockUserId = in_array($roleName, ['branch', 'service_center', 'annex']) ? (int) $user->id : null;

        foreach ($data['cartItems'] as $item) {
            if ($isHeadquarters) {
                $avail = HeadquartersStock::getQuantity($user->id, $item->product->id);
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
        $branchUserId = ($isHeadquarters || $stockUserId) ? (int) $user->id : null;

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
    if (!$request->user() || $order->user_id != $request->user()->id) {
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

        $stockUserId = in_array($user->role?->name ?? '', ['branch', 'service_center', 'annex']) ? (int) $user->id : null;
        $isHeadquarters = $user->role?->name === 'headquarters';
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

        if (! $user->canPayWithWallet($subtotal)) {
            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', 'Insufficient wallet balance. Need ₦' . number_format($subtotal, 0) . ' – you have ₦' . number_format($user->wallet_balance ?? 0, 0) . '.');
        }

        DB::transaction(function () use ($user, $order, $subtotal, $branchUserId, $isHeadquarters) {
            $order->update([
                'payment_method' => Order::PAYMENT_WALLET,
                'status' => Order::STATUS_PAID,
                'branch_user_id' => $branchUserId,
            ]);

            // For Headquarters users, deduct stock immediately when order is paid
            if ($isHeadquarters) {
                $order->load('items');
                foreach ($order->items as $item) {
                    $product = \App\Models\Product::where('item_code', $item->item_code)->first();
                    if ($product) {
                        HeadquartersStock::decrementStock($user->id, $product->id, $item->quantity);
                    }
                }
            }
            // For other users, stock will be deducted when order is marked as completed

            $user->decrement('wallet_balance', $subtotal);
            $balanceAfter = (float) $user->fresh()->wallet_balance;
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $subtotal,
                'balance_after' => $balanceAfter,
                'reference' => 'Order #' . $order->id,
            ]);
        });

        $request->session()->forget(['kd_id', 'customer_name']);

        $order->load(['user', 'items']);
        try {
            Mail::to($user->email)->send(new OrderConfirmationMail($order));
        } catch (\Throwable $e) {
            \Log::warning('Order confirmation email failed: ' . $e->getMessage());
        }

        return redirect()->route('orders.receipt', $order)->with('success', 'Order placed successfully. ₦' . number_format($subtotal, 0) . ' deducted from wallet.');
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
            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', 'Insufficient wallet balance. Need ₦' . number_format($totalAmount, 0) . ' – you have ₦' . number_format($user->wallet_balance ?? 0, 0) . '.');
        }

        // Check stock availability for all drafts before placing
        $stockUserId = in_array($user->role?->name ?? '', ['branch', 'service_center', 'annex']) ? (int) $user->id : null;
        $isHeadquarters = $user->role?->name === 'headquarters';
        $branchUserId = $stockUserId;

        foreach ($drafts as $order) {
            foreach ($order->items as $item) {
                $product = \App\Models\Product::where('item_code', $item->item_code)->first();
                if ($product) {
                    if ($isHeadquarters) {
                        $avail = HeadquartersStock::getQuantity($user->id, $product->id);
                        if ($avail < $item->quantity) {
                            return redirect()->route('orders.index', ['status' => 'draft'])->with('error', "Insufficient Headquarters stock for {$item->product_name} in order #{$order->invoice_number}. Available: {$avail}.");
                        }
                    } elseif ($stockUserId) {
                        $avail = $this->getStockForUser($stockUserId, $user->role?->name, $product->id);
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

        DB::transaction(function () use ($user, $drafts, $branchUserId, $isHeadquarters) {
            foreach ($drafts as $order) {
                $subtotal = (float) $order->subtotal;
                $order->update([
                    'payment_method' => Order::PAYMENT_WALLET,
                    'status' => Order::STATUS_PAID,
                    'branch_user_id' => $branchUserId,
                ]);
                
                // For Headquarters users, deduct stock immediately when order is paid
                if ($isHeadquarters) {
                    $order->load('items');
                    foreach ($order->items as $item) {
                        $product = \App\Models\Product::where('item_code', $item->item_code)->first();
                        if ($product) {
                            HeadquartersStock::decrementStock($user->id, $product->id, $item->quantity);
                        }
                    }
                }
                // For other users, stock will be deducted when order is marked as completed
                
                $user->decrement('wallet_balance', $subtotal);
                $balanceAfter = (float) $user->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $subtotal,
                    'balance_after' => $balanceAfter,
                    'reference' => 'Order #' . $order->id,
                ]);

                $order->load(['user', 'items']);
                try {
                    Mail::to($user->email)->send(new OrderConfirmationMail($order));
                } catch (\Throwable $e) {
                    \Log::warning('Order confirmation email failed: ' . $e->getMessage());
                }
            }
        });

        $request->session()->forget(['kd_id', 'customer_name']);

        $firstOrder = $drafts->first();
        $placedOrderIds = $drafts->pluck('id')->toArray();

        $request->session()->put('placed_order_ids', $placedOrderIds);

        return redirect()->route('orders.receipt', $firstOrder)
            ->with('success', 'All ' . $drafts->count() . ' draft(s) placed successfully. ₦' . number_format($totalAmount, 0) . ' deducted from wallet.');
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
     * Buy with DPBV - auto-generate KD NO and place order with DPBV (subtotal = 0).
     */
    public function buyWithDpbv(Request $request)
    {
        $data = $this->getCartData($request);
        if ($data['cartCount'] < 1) {
            return redirect()->route('home')->with('error', 'Your cart is empty.');
        }

        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to buy with DPBV.');
        }

        // Check DPBV balance
        $totalDpbv = (float) DpbvCollection::where('user_id', $user->id)->sum('dpbv');
        $dpbvNairaEquivalent = ($totalDpbv * 0.95) * 990;
        if ($dpbvNairaEquivalent < $data['cartSubtotal']) {
            return redirect()->route('home')->with('error', 'Insufficient DPBV balance. You have ₦' . number_format($dpbvNairaEquivalent, 2) . ' available.');
        }
        
        // Check if all products allow DPBV
        $productsNotAllowed = [];
        foreach ($data['cartItems'] as $item) {
            if (!($item->product->can_use_dpbv ?? true)) {
                $productsNotAllowed[] = $item->product->name;
            }
        }
        if (!empty($productsNotAllowed)) {
            return redirect()->route('home')->with('error', 'The following products cannot be purchased with DPBV: ' . implode(', ', $productsNotAllowed) . '. Please remove them from your cart.');
        }

        // Auto-generate KD NO and name
        $baseKd = 'KD-' . $user->id . '-';
        $existing = KdCustomer::where('kd_no', 'like', $baseKd . '%')->max('kd_no');
        $seq = 1;
        if ($existing) {
            $parts = explode('-', $existing);
            $seq = (int) (end($parts) ?: 0) + 1;
        }
        $kdNo = $baseKd . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
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
                
                // Deduct stock for Headquarters users
                if ($isHeadquarters) {
                    HeadquartersStock::decrementStock($user->id, $item->product->id, $item->quantity);
                }
            }

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
            \Log::warning('Order confirmation email failed: ' . $e->getMessage());
        }

        return redirect()->route('dashboard')->with('success', 'Order placed successfully with DPBV! KD NO: ' . $kdNo);
    }

    public function validateScCode(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:100',
        ]);

        $code = $request->input('code');

        $user = User::where('service_center_code', $code)
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
