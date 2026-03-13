<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KediKit;
use App\Models\KediKitBackOrder;
use App\Models\KediKitPurchase;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KediKitPurchaseController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = $user->role->name ?? '';

        // Get purchases where current user is the buyer
        $purchases = KediKitPurchase::with(['kit', 'buyer', 'seller', 'backOrders'])
            ->where('buyer_user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20);

        // Get back orders for this user
        $backOrders = KediKitBackOrder::with(['kit', 'purchase'])
            ->where('buyer_user_id', $user->id)
            ->where('status', KediKitBackOrder::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.kedi-kits.purchase.index', compact('purchases', 'backOrders'));
    }

    public function sellerDashboard(Request $request)
    {
        $user = $request->user();

        // Get purchases where current user is the seller
        $pendingPurchases = KediKitPurchase::with(['kit', 'buyer', 'backOrders'])
            ->where('seller_user_id', $user->id)
            ->where('status', KediKitPurchase::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        // Get all purchases from this seller
        $allPurchases = KediKitPurchase::with(['kit', 'buyer', 'backOrders'])
            ->where('seller_user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        // Get back orders for purchases where this user is the seller
        $backOrders = KediKitBackOrder::with(['kit', 'purchase', 'buyer'])
            ->whereHas('purchase', function ($q) use ($user) {
                $q->where('seller_user_id', $user->id);
            })
            ->where('status', KediKitBackOrder::STATUS_PENDING)
            ->orderByDesc('created_at')
            ->get();

        return view('admin.kedi-kits.purchase.seller-dashboard', compact('pendingPurchases', 'allPurchases', 'backOrders'));
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $roleName = $user->role->name ?? '';

        // Determine seller based on buyer's role
        $seller = $this->getSellerForBuyer($user);

        if (!$seller) {
            return redirect()->route('admin.kedi-kits.purchase.index')
                ->with('error', 'No seller available for your role. Only Headquarters, Branch, Service Center, and Annex can purchase kits.');
        }

        // Get available kits from seller (kits created by seller)
        $availableKits = KediKit::with(['createdBy', 'items'])
            ->where('created_by_user_id', $seller->id)
            ->orderByDesc('created_at')
            ->get();

        $walletBalance = (float) ($user->wallet_balance ?? 0);

        return view('admin.kedi-kits.purchase.create', compact('availableKits', 'seller', 'walletBalance'));
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $seller = $this->getSellerForBuyer($user);

        if (!$seller) {
            return back()->with('error', 'No seller available for your role.');
        }

        $validated = $request->validate([
            'kedi_kit_id' => ['required', 'integer', 'exists:kedi_kits,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        // Verify the kit belongs to the seller
        $kit = KediKit::findOrFail($validated['kedi_kit_id']);
        if ($kit->created_by_user_id !== $seller->id) {
            return back()->with('error', 'This kit is not available from your seller.');
        }

        $unitPrice = $kit->price;
        $requestedQuantity = $validated['quantity'];
        $availableQuantity = $kit->quantity ?? 0;
        
        // Calculate fulfilled and back order quantities
        $fulfilledQuantity = min($requestedQuantity, $availableQuantity);
        $backOrderQuantity = max(0, $requestedQuantity - $availableQuantity);
        
        $totalPrice = $unitPrice * $requestedQuantity;

        // Check wallet balance
        if (!$user->canPayWithWallet($totalPrice)) {
            return back()->withInput()
                ->with('error', 'Insufficient wallet balance. Your balance is ₦' . number_format($user->wallet_balance ?? 0, 2) . ' but you need ₦' . number_format($totalPrice, 2) . '.');
        }

        DB::beginTransaction();
        try {
            // Create purchase record
            $purchase = KediKitPurchase::create([
                'kedi_kit_id' => $kit->id,
                'buyer_user_id' => $user->id,
                'seller_user_id' => $seller->id,
                'quantity' => $requestedQuantity,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'status' => $fulfilledQuantity > 0 ? KediKitPurchase::STATUS_APPROVED : KediKitPurchase::STATUS_PENDING,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Deduct from wallet
            $user->decrement('wallet_balance', $totalPrice);
            $balanceAfter = (float) $user->fresh()->wallet_balance;

            // Create wallet transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => WalletTransaction::TYPE_DEBIT,
                'amount' => $totalPrice,
                'balance_after' => $balanceAfter,
                'reference' => 'KEDI Kit Purchase #' . $purchase->id,
                'status' => WalletTransaction::STATUS_ACCEPTED,
            ]);

            // Assign KD numbers to buyer ONLY for fulfilled quantity (when stock is available)
            // Don't assign for back orders - they'll be assigned when fulfilled
            if ($fulfilledQuantity > 0) {
                $unassignedItems = $kit->items()
                    ->whereNull('purchased_by_user_id')
                    ->limit($fulfilledQuantity)
                    ->get();
                
                foreach ($unassignedItems as $item) {
                    $item->update(['purchased_by_user_id' => $user->id]);
                }
                
                // Update kit quantity (deduct fulfilled quantity)
                $kit->decrement('quantity', $fulfilledQuantity);
            }

            // Create back order if needed
            if ($backOrderQuantity > 0) {
                KediKitBackOrder::create([
                    'kedi_kit_id' => $kit->id,
                    'purchase_id' => $purchase->id,
                    'buyer_user_id' => $user->id,
                    'quantity_pending' => $backOrderQuantity,
                    'quantity_fulfilled' => 0,
                    'status' => KediKitBackOrder::STATUS_PENDING,
                    'notes' => 'Back order for ' . $backOrderQuantity . ' kit(s)',
                ]);
            }

            DB::commit();

            $message = 'Kit purchase submitted successfully. ';
            if ($fulfilledQuantity > 0) {
                $message .= $fulfilledQuantity . ' kit(s) fulfilled. ';
            }
            if ($backOrderQuantity > 0) {
                $message .= $backOrderQuantity . ' kit(s) placed in back order. ';
            }
            $message .= '₦' . number_format($totalPrice, 2) . ' deducted from your wallet.';

            return redirect()->route('admin.kedi-kits.purchase.show', $purchase)
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create purchase request: ' . $e->getMessage());
        }
    }

    public function show(KediKitPurchase $purchase)
    {
        // Fresh load of purchase with all related data
        $purchase = KediKitPurchase::with([
            'kit',
            'buyer',
            'seller',
            'backOrders'
        ])->findOrFail($purchase->id);
        
        // Explicitly reload kit items with registration relationship (fresh query)
        $kit = $purchase->kit;
        $kit->load(['items' => function ($query) {
            $query->with('purchasedBy', 'registration');
        }]);
        
        // Clean up back orders: delete invalid ones and update status
        $assignedToBuyer = $kit->items()->where('purchased_by_user_id', $purchase->buyer_user_id)->count();
        
        // If all KD numbers are assigned (purchase fully fulfilled), delete any back orders
        if ($assignedToBuyer >= $purchase->quantity) {
            // Delete back orders since purchase is fully fulfilled
            foreach ($purchase->backOrders as $backOrder) {
                $backOrder->delete();
            }
            // Mark purchase as completed IF fully registered
            if ($purchase->status !== KediKitPurchase::STATUS_COMPLETED && $purchase->isFullyRegistered()) {
                $purchase->update(['status' => KediKitPurchase::STATUS_COMPLETED]);
            }
        } else {
            // Update back order status if quantity_pending is 0
            $totalPending = 0;
            foreach ($purchase->backOrders as $backOrder) {
                if ($backOrder->quantity_pending == 0) {
                    // Mark as fulfilled if no pending quantity
                    if ($backOrder->status === KediKitBackOrder::STATUS_PENDING) {
                        $backOrder->update(['status' => KediKitBackOrder::STATUS_FULFILLED]);
                    }
                } else {
                    $totalPending += $backOrder->quantity_pending;
                }
            }
            
            // If purchase is approved and all back orders are fulfilled, mark purchase as completed IF fully registered
            if ($purchase->status === KediKitPurchase::STATUS_APPROVED && $totalPending == 0 && $purchase->backOrders->count() > 0 && $purchase->isFullyRegistered()) {
                $purchase->update(['status' => KediKitPurchase::STATUS_COMPLETED]);
            }
        }
        
        // Reload back orders and purchase after potential updates
        $purchase->load('backOrders');
        $purchase->refresh();
        
        return view('admin.kedi-kits.purchase.show', compact('purchase'));
    }

    public function approve(Request $request, KediKitPurchase $purchase)
    {
        // Only seller can approve
        if ($purchase->seller_user_id !== $request->user()->id) {
            return back()->with('error', 'You are not authorized to approve this purchase.');
        }

        // Can only approve pending purchases
        if ($purchase->status !== KediKitPurchase::STATUS_PENDING) {
            return back()->with('error', 'This purchase cannot be approved. Current status: ' . $purchase->status);
        }

        $kit = $purchase->kit;
        $availableQuantity = $kit->quantity ?? 0;
        $requestedQuantity = $purchase->quantity;

        DB::beginTransaction();
        try {
            // Calculate fulfilled and back order quantities
            $fulfilledQuantity = min($requestedQuantity, $availableQuantity);
            $backOrderQuantity = max(0, $requestedQuantity - $availableQuantity);

            // Update purchase status
            $purchase->update([
                'status' => KediKitPurchase::STATUS_APPROVED,
            ]);

            // Assign KD numbers to buyer for the FULL purchase quantity (not just fulfilled)
            // This marks them as "sold" even if they're in back order
            // Get unassigned KD numbers for this purchase
            $unassignedItems = $kit->items()
                ->whereNull('purchased_by_user_id')
                ->limit($requestedQuantity)
                ->get();
            
            foreach ($unassignedItems as $item) {
                $item->update(['purchased_by_user_id' => $purchase->buyer_user_id]);
            }

            // Deduct fulfilled quantity from kit (only for physically available stock)
            if ($fulfilledQuantity > 0) {
                $kit->decrement('quantity', $fulfilledQuantity);
            }

            // Create back order if needed
            if ($backOrderQuantity > 0) {
                KediKitBackOrder::create([
                    'kedi_kit_id' => $kit->id,
                    'purchase_id' => $purchase->id,
                    'buyer_user_id' => $purchase->buyer_user_id,
                    'quantity_pending' => $backOrderQuantity,
                    'quantity_fulfilled' => 0,
                    'status' => KediKitBackOrder::STATUS_PENDING,
                    'notes' => 'Back order for ' . $backOrderQuantity . ' kit(s)',
                ]);
            }

            // If all fulfilled, mark as completed ONLY if all registered
            if ($backOrderQuantity === 0 && $purchase->isFullyRegistered()) {
                $purchase->update([
                    'status' => KediKitPurchase::STATUS_COMPLETED,
                ]);
            }

            DB::commit();

            $message = 'Purchase approved successfully. ';
            if ($fulfilledQuantity > 0) {
                $message .= $fulfilledQuantity . ' kit(s) fulfilled. ';
            }
            if ($backOrderQuantity > 0) {
                $message .= $backOrderQuantity . ' kit(s) placed in back order.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to approve purchase: ' . $e->getMessage());
        }
    }

    public function reject(Request $request, KediKitPurchase $purchase)
    {
        // Only seller can reject
        if ($purchase->seller_user_id !== $request->user()->id) {
            return back()->with('error', 'You are not authorized to reject this purchase.');
        }

        if ($purchase->status !== KediKitPurchase::STATUS_PENDING) {
            return back()->with('error', 'This purchase cannot be rejected.');
        }

        DB::beginTransaction();
        try {
            // Refund wallet
            $buyer = $purchase->buyer;
            $buyer->increment('wallet_balance', $purchase->total_price);
            $balanceAfter = (float) $buyer->fresh()->wallet_balance;

            // Create refund transaction
            WalletTransaction::create([
                'user_id' => $buyer->id,
                'type' => WalletTransaction::TYPE_CREDIT,
                'amount' => $purchase->total_price,
                'balance_after' => $balanceAfter,
                'reference' => 'Refund for KEDI Kit Purchase #' . $purchase->id,
                'status' => WalletTransaction::STATUS_ACCEPTED,
            ]);

            $purchase->update([
                'status' => KediKitPurchase::STATUS_REJECTED,
            ]);

            DB::commit();

            return back()->with('success', 'Purchase rejected. Amount refunded to buyer\'s wallet.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to reject purchase: ' . $e->getMessage());
        }
    }

    public function unassignKdNumbers(Request $request, KediKitPurchase $purchase)
    {
        $user = $request->user();
        
        // Only buyer or seller can unassign
        if ($purchase->buyer_user_id !== $user->id && $purchase->seller_user_id !== $user->id) {
            return back()->with('error', 'You are not authorized to unassign KD numbers from this purchase.');
        }

        DB::beginTransaction();
        try {
            $kit = $purchase->kit;
            
            // Get all KD numbers assigned to this purchase's buyer from this kit
            $assignedItems = $kit->items()
                ->where('purchased_by_user_id', $purchase->buyer_user_id)
                ->get();

            $unassignCount = 0;
            
            // Unassign (set purchased_by_user_id to null) and mark as pending
            foreach ($assignedItems as $item) {
                $item->update(['purchased_by_user_id' => null]);
                $unassignCount++;
            }

            // Re-add stock to kit (for items that were fulfilled)
            $kit->increment('quantity', $unassignCount);

            // Reset purchase status to pending
            $purchase->update([
                'status' => KediKitPurchase::STATUS_PENDING,
            ]);

            // Delete any associated back orders for this purchase
            $purchase->backOrders()->delete();

            DB::commit();

            return back()->with('success', $unassignCount . ' KD number(s) have been unassigned and made pending. Purchase status reset to pending.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to unassign KD numbers: ' . $e->getMessage());
        }
    }

    public function fulfillBackOrder(Request $request, KediKitBackOrder $backOrder)
    {
        $user = $request->user();
        
        // Only seller can fulfill back orders
        if ($backOrder->purchase->seller_user_id !== $user->id) {
            return back()->with('error', 'You are not authorized to fulfill this back order.');
        }

        if ($backOrder->status !== KediKitBackOrder::STATUS_PENDING) {
            return back()->with('error', 'This back order cannot be fulfilled.');
        }

        $request->validate([
            'fulfill_quantity' => ['required', 'integer', 'min:1', 'max:' . $backOrder->quantity_pending],
        ]);

        $fulfillQuantity = $request->input('fulfill_quantity');
        $kit = $backOrder->kit;
        $availableQuantity = $kit->quantity ?? 0;

        if ($fulfillQuantity > $availableQuantity) {
            return back()->with('error', 'Insufficient stock. Available: ' . $availableQuantity . ', Requested: ' . $fulfillQuantity);
        }

        DB::beginTransaction();
        try {
            // Deduct from kit quantity and assign KD numbers to buyer
            $kit->decrement('quantity', $fulfillQuantity);
            
            // Assign KD numbers to buyer (get unassigned KD numbers)
            $unassignedItems = $kit->items()
                ->whereNull('purchased_by_user_id')
                ->limit($fulfillQuantity)
                ->get();
            
            foreach ($unassignedItems as $item) {
                $item->update(['purchased_by_user_id' => $backOrder->buyer_user_id]);
            }

            // Update back order
            $newPending = $backOrder->quantity_pending - $fulfillQuantity;
            $newFulfilled = $backOrder->quantity_fulfilled + $fulfillQuantity;

            $backOrder->update([
                'quantity_pending' => $newPending,
                'quantity_fulfilled' => $newFulfilled,
                'status' => $newPending > 0 ? KediKitBackOrder::STATUS_PENDING : KediKitBackOrder::STATUS_FULFILLED,
            ]);

            // Update purchase status if all fulfilled and all registered
            if ($newPending === 0 && $backOrder->purchase->isFullyRegistered()) {
                $backOrder->purchase->update([
                    'status' => KediKitPurchase::STATUS_COMPLETED,
                ]);
            }

            DB::commit();

            return back()->with('success', $fulfillQuantity . ' kit(s) fulfilled. ' . ($newPending > 0 ? $newPending . ' still pending.' : 'Back order completed.'));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to fulfill back order: ' . $e->getMessage());
        }
    }

    public function syncRegistrations(Request $request, KediKitPurchase $purchase)
    {
        $user = $request->user();
        
        // Only buyer can sync
        if ($purchase->buyer_user_id !== $user->id) {
            return back()->with('error', 'You are not authorized to sync registrations for this purchase.');
        }

        DB::beginTransaction();
        try {
            $kit = $purchase->kit;
            $buyerId = $purchase->buyer_user_id;

            // Get all registrations for this user that ARE NOT already linked to a kit item
            // Actually, we just need to satisfy the purchase quantity
            $registrations = \App\Models\KdRegistration::where('user_id', $buyerId)
                ->whereDoesntHave('kitItem')
                ->get();

            $linkedCount = 0;
            
            foreach ($registrations as $reg) {
                // Find an unassigned kit item from this kit
                $item = \App\Models\KediKitItem::where('kedi_kit_id', $kit->id)
                    ->whereNull('purchased_by_user_id')
                    ->first();
                
                if ($item) {
                   $item->update([
                       'kd_no' => $reg->kd_no,
                       'purchased_by_user_id' => $buyerId,
                       'kedi_kit_purchase_id' => $purchase->id
                   ]);
                   $linkedCount++;
                   
                   // Deduct from purchase quantity
                   $purchase->decrement('quantity', 1);
                   
                   // Deduct from kit quantity if needed
                   if ($kit->quantity > 0) {
                       $kit->decrement('quantity', 1);
                   }
                } else {
                    // Create a new kit item if none available to assign
                    KediKitItem::create([
                        'kedi_kit_id' => $kit->id,
                        'kedi_kit_purchase_id' => $purchase->id,
                        'kd_no' => $reg->kd_no,
                        'purchased_by_user_id' => $buyerId
                    ]);
                    $linkedCount++;
                    
                    // Deduct from purchase quantity
                    $purchase->decrement('quantity', 1);
                    
                    // Deduct from kit quantity if needed
                    if ($kit->quantity > 0) {
                        $kit->decrement('quantity', 1);
                    }
                }
                
                // Stop if we've satisfied the purchase quantity
                $assignedCount = $kit->items()->where('purchased_by_user_id', $buyerId)->count();
                if ($assignedCount >= $purchase->quantity) {
                    break;
                }
            }

            // Check if purchase is now completed
            if ($purchase->status !== KediKitPurchase::STATUS_COMPLETED && $purchase->isFullyRegistered()) {
                $purchase->update(['status' => KediKitPurchase::STATUS_COMPLETED]);
            }

            DB::commit();

            return back()->with('success', $linkedCount . ' registration(s) have been synced and linked to your purchase.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Failed to sync registrations: ' . $e->getMessage());
        }
    }

    /**
     * Determine seller based on buyer's role
     * Hierarchy: HQ buys from Super Admin, Branch buys from HQ, Service Center buys from Branch, Annex buys from Service Center
     */
    private function getSellerForBuyer(User $buyer): ?User
    {
        $roleName = $buyer->role->name ?? '';

        switch ($roleName) {
            case 'headquarters':
                // HQ buys from Super Admin (find a super admin user)
                return User::whereHas('role', function ($q) {
                    $q->where('name', 'super_admin');
                })->first();

            case 'branch':
                // Branch buys from Headquarters (find the HQ that created this branch)
                return User::where('id', $buyer->created_by_user_id)
                    ->whereHas('role', function ($q) {
                        $q->where('name', 'headquarters');
                    })->first();

            case 'service_center':
                // Service Center buys from Branch (find the Branch that created this service center)
                return User::where('id', $buyer->created_by_user_id)
                    ->whereHas('role', function ($q) {
                        $q->where('name', 'branch');
                    })->first();

            case 'annex':
                // Annex buys from Service Center (find the Service Center that created this annex)
                return User::where('id', $buyer->created_by_user_id)
                    ->whereHas('role', function ($q) {
                        $q->where('name', 'service_center');
                    })->first();

            case 'reseller':
            case 'customer':
                // Reseller and Customer buy from their creator, or fall back to Super Admin
                $seller = User::find($buyer->created_by_user_id);
                if ($seller) {
                    return $seller;
                }
                return User::whereHas('role', function ($q) {
                    $q->where('name', 'super_admin');
                })->first();

            default:
                return null;
        }
    }
}
