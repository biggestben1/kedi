<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KdCustomerController extends Controller
{
    /**
     * Show all auto-generated KD numbers and My Orders with Share to Friend.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $userPrefix = 'KD-' . $user->id . '-';
        
        $query = KdCustomer::with('user')
            ->where('kd_no', 'like', $userPrefix . '%');

        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        if ($driver === 'mysql') {
            $query->whereRaw("kd_no REGEXP '^KD-[0-9]+-[0-9]+$'");
        } elseif ($driver === 'pgsql') {
            $query->whereRaw("kd_no ~ '^KD-[0-9]+-[0-9]+$'");
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('kd_no', 'like', '%' . $search . '%')
                    ->orWhere('customer_name', 'like', '%' . $search . '%')
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%'));
            });
        }

        $kdCustomers = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        $kdCustomersGrouped = $kdCustomers->getCollection()->groupBy(function ($kd) {
            $parts = explode('-', $kd->kd_no);
            return ($parts[0] ?? '') . '-' . ($parts[1] ?? '');
        });

        $myOrders = Order::with(['items'])
            ->where('user_id', $request->user()->id)
            ->whereNotIn('status', [Order::STATUS_CANCELLED])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return view('admin.kd.index', [
            'kdCustomers' => $kdCustomers,
            'kdCustomersGrouped' => $kdCustomersGrouped,
            'search' => $request->query('search'),
            'myOrders' => $myOrders,
        ]);
    }

    /**
     * Show share page (optionally for a specific KD).
     */
    public function showShare(Request $request, ?KdCustomer $kd = null)
    {
        $user = $request->user();
        if (!$user) {
            return redirect()->route('login')->with('error', 'Please login to share products.');
        }

        $query = Order::with(['items'])
            ->where('user_id', $user->id)
            ->whereNotIn('status', [Order::STATUS_CANCELLED]);

        // If a specific KD is provided, filter orders by that KD number
        if ($kd && $kd->kd_no) {
            $query->where('kd_id', $kd->kd_no);
        }

        $myOrders = $query->orderByDesc('created_at')
            ->limit(50)
            ->get();

        // Debug: Log orders for troubleshooting
        \Log::info('Share page - User ID: ' . $user->id . ', Orders found: ' . $myOrders->count());

        return view('admin.kd.share', [
            'kd' => $kd,
            'myOrders' => $myOrders,
        ]);
    }

    /**
     * Share products from order items to a friend's KD NO.
     */
    public function share(Request $request)
    {
        $request->validate([
            'order_item_id' => 'required|array',
            'order_item_id.*' => 'required|exists:order_items,id',
            'quantity' => 'required|array',
            'quantity.*' => 'required|integer|min:1',
            'friend_kd_no' => 'required|string|max:100',
            'friend_name' => 'required|string|max:255',
        ]);

        $user = $request->user();
        $friendKdNo = trim($request->friend_kd_no);
        $friendName = trim($request->friend_name);

        if (empty($friendKdNo) || empty($friendName)) {
            return back()->with('error', 'Please provide friend\'s KD NO and Name.');
        }

        // Find or create KdCustomer
        $friendKd = KdCustomer::where('kd_no', $friendKdNo)
            ->orWhere('kd_no', strtoupper($friendKdNo))
            ->orWhere('kd_no', strtolower($friendKdNo))
            ->first();

        // Create KdCustomer if it doesn't exist
        if (! $friendKd) {
            $friendKd = KdCustomer::create([
                'kd_no' => $friendKdNo,
                'customer_name' => $friendName,
                'user_id' => null,
            ]);
        } else {
            // Update customer name if provided
            if ($friendKd->customer_name !== $friendName) {
                $friendKd->update(['customer_name' => $friendName]);
            }
        }

        // If friend doesn't have a user account, create a guest user account
        if (! $friendKd->user_id) {
            $customerRole = Role::where('name', 'customer')->first();
            if (! $customerRole) {
                return back()->with('error', 'Customer role not found. Please contact administrator.');
            }

            // Create a guest user account for the friend
            $friendUser = User::create([
                'name' => $friendName,
                'email' => 'guest_' . str_replace(['-', ' '], '_', strtolower($friendKdNo)) . '_' . time() . '@shared.local',
                'password' => bcrypt(uniqid('guest_', true)),
                'role_id' => $customerRole->id,
            ]);

            // Link KD to the new user
            $friendKd->update(['user_id' => $friendUser->id]);
            $friendKd->refresh();
        }

        if ($friendKd->user_id === $user->id) {
            return back()->with('error', 'You cannot share to yourself.');
        }

        $itemIds = $request->order_item_id;
        $quantities = $request->quantity;
        $sharedItems = [];
        $errors = [];

        DB::transaction(function () use ($user, $friendKd, $friendName, $itemIds, $quantities, &$sharedItems, &$errors) {
            foreach ($itemIds as $index => $itemId) {
                $orderItem = OrderItem::with('order')->findOrFail($itemId);

                // Check if order exists and belongs to user
                if (!$orderItem->order) {
                    $errors[] = "Order not found for item #{$itemId}.";
                    continue;
                }

                // Use loose comparison to handle string/int type differences
                if ((int) $orderItem->order->user_id !== (int) $user->id) {
                    $errors[] = "You can only share from your own orders. Order #{$orderItem->order->id} belongs to user #{$orderItem->order->user_id}, but you are user #{$user->id}.";
                    continue;
                }

                $quantity = (int) ($quantities[$index] ?? 1);
                if ($quantity > $orderItem->quantity) {
                    $errors[] = "You can share at most {$orderItem->quantity} of {$orderItem->product_name}.";
                    continue;
                }

                $unitPrice = $orderItem->unit_price;
                $lineTotal = $unitPrice * $quantity;
                $bvPerUnit = $orderItem->bv / $orderItem->quantity;
                $pvPerUnit = $orderItem->pv / $orderItem->quantity;
                $bv = $bvPerUnit * $quantity;
                $pv = $pvPerUnit * $quantity;

                // Create order for friend
                $order = Order::create([
                    'user_id' => $friendKd->user_id,
                    'kd_id' => $friendKd->kd_no,
                    'customer_name' => $friendName,
                    'delivery_type' => Order::DELIVERY_WALK_IN,
                    'invoice_number' => Order::generateOrderNumber(),
                    'subtotal' => $lineTotal,
                    'total_bv' => $bv,
                    'total_pv' => $pv,
                    'payment_method' => Order::PAYMENT_PAY_ON_DELIVERY,
                    'status' => Order::STATUS_PENDING,
                    'shipping_address' => 'Shared – Walk-in',
                    'shipping_city' => '',
                    'shipping_state' => '',
                    'shipping_postal_code' => '',
                    'shipping_phone' => '',
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $orderItem->item_code,
                    'product_name' => $orderItem->product_name,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'bv' => $bv,
                    'pv' => $pv,
                ]);

                // Deduct from original order item
                $remainingQty = $orderItem->quantity - $quantity;
                
                if ($remainingQty <= 0) {
                    // Delete the order item if all quantity is shared
                    $parentOrder = $orderItem->order;
                    $orderItem->delete();
                } else {
                    // Update the order item with remaining quantity
                    $remainingLineTotal = $unitPrice * $remainingQty;
                    $remainingBv = $bvPerUnit * $remainingQty;
                    $remainingPv = $pvPerUnit * $remainingQty;

                    $orderItem->update([
                        'quantity' => $remainingQty,
                        'line_total' => $remainingLineTotal,
                        'bv' => $remainingBv,
                        'pv' => $remainingPv,
                    ]);
                    $parentOrder = $orderItem->order;
                }

                // Update parent order totals
                $parentOrder->refresh();
                $parentOrder->load('items');
                $parentOrder->subtotal = $parentOrder->items->sum('line_total');
                $parentOrder->total_bv = $parentOrder->items->sum('bv');
                $parentOrder->total_pv = $parentOrder->items->sum('pv');
                $parentOrder->save();

                $sharedItems[] = "{$quantity} x {$orderItem->product_name}";
            }
        });

        if (! empty($errors)) {
            $errorMessage = count($errors) > 1 ? implode(' ', $errors) : $errors[0];
            return back()->with('error', $errorMessage);
        }

        if (empty($sharedItems)) {
            return back()->with('error', 'No items were shared. Please select items to share.');
        }

        $message = 'Shared ' . implode(', ', $sharedItems) . " to {$friendName} (KD: {$friendKd->kd_no}). They will see it in My Orders.";
        return back()->with('success', $message);
    }
}
