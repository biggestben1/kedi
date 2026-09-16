<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\KdRegistrationCredit;
use App\Models\KediKitItem;
use App\Models\KediKitPurchase;
use App\Models\KediCreditTransaction;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KdRegistrationController extends Controller
{
    public const REGISTRATION_FEE = 12000.00;

    /**
     * Wallet used for KD registration fees (cashier → parent; distributor → own).
     */
    private function resolveWalletOwner(User $user): User
    {
        $user->loadMissing(['role', 'createdBy.role']);

        return $user->walletOwnerForShopping();
    }

    /**
     * Active open order group from session, if any.
     */
    private function resolveActiveOrderGroup(Request $request, User $user): ?OrderGroup
    {
        $groupId = (int) $request->session()->get('order_group_id', 0);
        if ($groupId < 1) {
            return null;
        }

        $group = OrderGroup::where('id', $groupId)
            ->where('user_id', $user->id)
            ->where('status', OrderGroup::STATUS_OPEN)
            ->first();

        if (! $group) {
            $request->session()->forget('order_group_id');
        }

        return $group;
    }

    /**
     * Display a listing of KD registrations.
     */
    public function index(Request $request)
    {
        // Requirement: show Service Center users from users table (not kd_registrations).
        $query = User::query()
            ->with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', Role::SERVICE_CENTER);
            });

        if ($request->filled('search')) {
            $search = trim((string) $request->query('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('service_center_code', 'like', '%'.$search.'%')
                    ->orWhere('kid', 'like', '%'.$search.'%');
            });
        }

        $serviceCenters = $query
            ->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.kd.registration.index', [
            'serviceCenters' => $serviceCenters,
            'search' => $request->query('search'),
        ]);
    }

    /**
     * Show the form for creating a new KD registration.
     */
    public function create(Request $request)
    {
        $user = auth()->user();
        $walletOwner = $this->resolveWalletOwner($user);
        $walletBalance = $walletOwner->wallet_balance ?? 0;
        $users = User::with('role')->orderBy('name')->get();
        $activeOrderGroup = $this->resolveActiveOrderGroup($request, $user);
        $openOrderGroups = OrderGroup::where('user_id', $user->id)
            ->where('status', OrderGroup::STATUS_OPEN)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get();

        return view('admin.kd.registration.create', [
            'users' => $users,
            'walletBalance' => $walletBalance,
            'activeOrderGroup' => $activeOrderGroup,
            'openOrderGroups' => $openOrderGroups,
            'registrationFee' => self::REGISTRATION_FEE,
        ]);
    }

    /**
     * Store a newly created KD registration.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'kd_no' => 'required|string|max:100|unique:kd_registrations,kd_no',
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'state' => 'required|string|max:100',
            'full_address' => 'required|string',
            'phone_number' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'user_id' => 'nullable|integer|exists:users,id',
            'sponsor_kd_no' => 'required|string|max:100',
            'sponsor_name' => 'required|string|max:255',
            'placement_kd_no' => 'nullable|string|max:100',
            'placement_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'registration_type' => 'nullable|in:new,old',
            'add_to_order_group' => 'nullable|boolean',
            'order_group_id' => 'nullable|integer|exists:order_groups,id',
        ]);

        $user = $request->user();
        $user->loadMissing(['role', 'createdBy.role']);
        $walletOwner = $this->resolveWalletOwner($user);
        $registrationType = $request->input('registration_type', 'new');
        $addToOrderGroup = $request->boolean('add_to_order_group') && ! $request->has('from_kit');
        // from_kit OR "old" registration type => no fee
        $registrationFee = $request->has('from_kit') || $registrationType === 'old' ? 0.00 : self::REGISTRATION_FEE;

        $orderGroup = null;
        if ($addToOrderGroup) {
            $groupId = (int) ($validated['order_group_id'] ?? $request->session()->get('order_group_id', 0));
            $orderGroup = OrderGroup::where('id', $groupId)
                ->where('user_id', $user->id)
                ->where('status', OrderGroup::STATUS_OPEN)
                ->first();

            if (! $orderGroup) {
                return back()->withInput()
                    ->with('error', 'No open order group selected. Create or activate a group first, then add the registration fee to it.');
            }

            // Fee is collected later via group pay-all (not wallet now).
            $registrationFee = $registrationType === 'old' ? 0.00 : self::REGISTRATION_FEE;
        }

        // Check wallet balance only when charging wallet immediately (not when adding to group)
        if (! $addToOrderGroup && $registrationFee > 0 && ! $walletOwner->canPayWithWallet($registrationFee)) {
            return back()->withInput()
                ->with('error', 'Insufficient wallet balance. Your balance is ₦'.number_format($walletOwner->wallet_balance ?? 0, 2).' but you need ₦'.number_format($registrationFee, 2).' for registration.');
        }

        DB::beginTransaction();
        try {

            // Format sponsor and placement KD NO (ensure KN prefix)
            $sponsorKdNo = trim($validated['sponsor_kd_no']);
            if ($sponsorKdNo && ! str_starts_with(strtoupper($sponsorKdNo), 'KN')) {
                $sponsorKdNo = 'KN'.ltrim($sponsorKdNo, '-');
            }

            $placementKdNo = null;
            if ($validated['placement_kd_no']) {
                $placementKdNo = trim($validated['placement_kd_no']);
                if ($placementKdNo && ! str_starts_with(strtoupper($placementKdNo), 'KN')) {
                    $placementKdNo = 'KN'.ltrim($placementKdNo, '-');
                }
            }

            $registration = KdRegistration::create([
                'kd_no' => strtoupper(trim($validated['kd_no'])),
                'full_name' => trim($validated['full_name']),
                'gender' => $validated['gender'],
                'state' => trim($validated['state']),
                'full_address' => trim($validated['full_address']),
                'phone_number' => trim($validated['phone_number']),
                'registration_date' => $validated['registration_date'],
                'user_id' => $validated['user_id'] ?? null,
                'sponsor_kd_no' => strtoupper($sponsorKdNo),
                'sponsor_name' => trim($validated['sponsor_name']),
                'placement_kd_no' => $placementKdNo ? strtoupper($placementKdNo) : null,
                'placement_name' => $validated['placement_name'] ? trim($validated['placement_name']) : null,
                'registered_by_user_id' => $user->id,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Also create/update kd_customers entry
            KdCustomer::updateOrCreate(
                ['kd_no' => $registration->kd_no],
                [
                    'customer_name' => $registration->full_name,
                    'user_id' => $registration->user_id,
                ]
            );

            if ($addToOrderGroup && $orderGroup && $registrationFee > 0) {
                $this->attachRegistrationFeeToGroup($user, $orderGroup, $registration, $registrationFee);
                $request->session()->put('order_group_id', $orderGroup->id);
            } elseif (! $addToOrderGroup && $registrationFee > 0) {
                // Deduct registration fee from wallet if paying now
                $walletOwner->decrement('wallet_balance', $registrationFee);
                $balanceAfter = (float) $walletOwner->fresh()->wallet_balance;

                WalletTransaction::create([
                    'user_id' => $walletOwner->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $registrationFee,
                    'balance_after' => $balanceAfter,
                    'reference' => 'KD Registration Fee - KD NO: '.$registration->kd_no,
                    'status' => WalletTransaction::STATUS_ACCEPTED,
                ]);
            }

            DB::commit();

            // If registering from kit, check if the purchase is now complete
            if ($request->has('from_kit') && $request->filled('purchase_id')) {
                $purchase = KediKitPurchase::find($request->input('purchase_id'));
                if ($purchase) {
                    // Normalize the KD number for searching (remove spaces and convert to uppercase)
                    $normalizedKdNo = str_replace(' ', '', strtoupper(trim($registration->kd_no)));

                    // Try to find a kit item with this KD NO (normalized)
                    $kitItem = KediKitItem::whereRaw("REPLACE(kd_no, ' ', '') = ?", [$normalizedKdNo])
                        ->where('kedi_kit_id', $purchase->kedi_kit_id)
                        ->whereNull('purchased_by_user_id')
                        ->first();

                    // If no exact match, just pick any unassigned kit item from this kit
                    if (! $kitItem) {
                        $kitItem = KediKitItem::where('kedi_kit_id', $purchase->kedi_kit_id)
                            ->whereNull('purchased_by_user_id')
                            ->first();

                        if ($kitItem) {
                            // Update the kit item's KD number and link to purchase
                            $kitItem->kd_no = $registration->kd_no;
                            $kitItem->kedi_kit_purchase_id = $purchase->id;
                        } else {
                            // Create a new kit item if none available to assign
                            $kitItem = KediKitItem::create([
                                'kedi_kit_id' => $purchase->kedi_kit_id,
                                'kedi_kit_purchase_id' => $purchase->id,
                                'kd_no' => $registration->kd_no,
                                'purchased_by_user_id' => $purchase->buyer_user_id,
                            ]);
                        }
                    }

                    if ($kitItem->wasRecentlyCreated || ! $kitItem->purchased_by_user_id || ! $kitItem->kedi_kit_purchase_id) {
                        $kitItem->purchased_by_user_id = $purchase->buyer_user_id;
                        $kitItem->kedi_kit_purchase_id = $purchase->id;
                        $kitItem->save();

                        // Deduct from purchase quantity (rendering 11 -> 10 -> 9 etc.)
                        $purchase->decrement('quantity', 1);

                        // Deduct from kit quantity if it's currently available in stock
                        if ($purchase->kit->quantity > 0) {
                            $purchase->kit->decrement('quantity', 1);
                        }
                    }

                    if ($purchase->status !== KediKitPurchase::STATUS_COMPLETED && $purchase->isFullyRegistered()) {
                        $purchase->update(['status' => KediKitPurchase::STATUS_COMPLETED]);
                    }
                }
            }

            // If registering from kit, redirect back to purchase page
            if ($request->has('from_kit') && $request->filled('purchase_id')) {
                return redirect()->route('admin.kedi-kits.purchase.show', $request->input('purchase_id'))
                    ->with('success', 'KD registration created successfully. KD NO: '.$registration->kd_no.'. ₦'.number_format($registrationFee, 2).' deducted from your wallet.');
            }

            if ($addToOrderGroup && $orderGroup) {
                $feeMsg = $registrationFee > 0
                    ? ' ₦'.number_format($registrationFee, 2).' registration fee added to group "'.$orderGroup->displayName().'". Pay all when ready.'
                    : ' No fee added (old registration).';

                return redirect()
                    ->route('order-groups.show', $orderGroup)
                    ->with('success', 'KD registration created. KD NO: '.$registration->kd_no.'.'.$feeMsg);
            }

            return redirect()->route('admin.kd.registration.index')
                ->with('success', 'KD registration created successfully. KD NO: '.$registration->kd_no.'. ₦'.number_format($registrationFee, 2).' deducted from your wallet.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to create KD registration: '.$e->getMessage());
        }
    }

    /**
     * Attach ₦12,000 KD registration fee as a draft order on the group (pay-all later).
     */
    private function attachRegistrationFeeToGroup(User $user, OrderGroup $orderGroup, KdRegistration $registration, float $fee): void
    {
        $order = Order::create([
            'user_id' => $user->id,
            'order_group_id' => $orderGroup->id,
            'branch_user_id' => null,
            'kd_id' => $registration->kd_no,
            'customer_name' => $registration->full_name,
            'delivery_type' => Order::DELIVERY_WALK_IN,
            'invoice_number' => Order::generateOrderNumber(),
            'subtotal' => $fee,
            'total_bv' => 0,
            'total_pv' => 0,
            'payment_method' => Order::PAYMENT_PAY_ON_DELIVERY,
            'status' => Order::STATUS_DRAFT,
            'shipping_address' => 'Walk-in (Pick up)',
            'shipping_city' => '',
            'shipping_state' => '',
            'shipping_postal_code' => '',
            'shipping_phone' => $user->phone ?? '',
            'notes' => 'KD Registration Fee - KD NO: '.$registration->kd_no,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'item_code' => 'KD-REG-FEE',
            'product_name' => 'KD Registration Fee – '.$registration->kd_no,
            'quantity' => 1,
            'unit_price' => $fee,
            'line_total' => $fee,
            'bv' => 0,
            'pv' => 0,
        ]);

        $orderGroup->update([
            'total_amount' => (float) $orderGroup->draftOrders()->sum('subtotal'),
        ]);
    }

    /**
     * Display the specified KD registration.
     */
    public function show(KdRegistration $registration)
    {
        $registration->load(['user', 'registeredBy', 'credits.createdBy']);

        // Calculate current credit balance from all transactions
        $credits = $registration->credits()->with('createdBy')->orderByDesc('created_at')->get();
        $creditBalance = $credits->sum(function ($credit) {
            return $credit->type === KdRegistrationCredit::TYPE_CREDIT
                ? $credit->amount
                : -$credit->amount;
        });

        return view('admin.kd.registration.show', [
            'registration' => $registration,
            'creditBalance' => $creditBalance,
            'credits' => $credits,
        ]);
    }

    /**
     * Show the form for editing the specified KD registration.
     */
    public function edit(KdRegistration $registration)
    {
        $users = User::with('role')->orderBy('name')->get();

        return view('admin.kd.registration.edit', ['registration' => $registration, 'users' => $users]);
    }

    /**
     * Update the specified KD registration.
     */
    public function update(Request $request, KdRegistration $registration)
    {
        $validated = $request->validate([
            'kd_no' => 'required|string|max:100|unique:kd_registrations,kd_no,'.$registration->id,
            'full_name' => 'required|string|max:255',
            'gender' => 'required|in:M,F',
            'state' => 'required|string|max:100',
            'full_address' => 'required|string',
            'phone_number' => 'required|string|max:50',
            'registration_date' => 'required|date',
            'user_id' => 'nullable|integer|exists:users,id',
            'sponsor_kd_no' => 'required|string|max:100',
            'sponsor_name' => 'required|string|max:255',
            'placement_kd_no' => 'nullable|string|max:100',
            'placement_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Format sponsor and placement KD NO (ensure KN prefix)
        $sponsorKdNo = trim($validated['sponsor_kd_no']);
        if ($sponsorKdNo && ! str_starts_with(strtoupper($sponsorKdNo), 'KN')) {
            $sponsorKdNo = 'KN'.ltrim($sponsorKdNo, '-');
        }

        $placementKdNo = null;
        if ($validated['placement_kd_no']) {
            $placementKdNo = trim($validated['placement_kd_no']);
            if ($placementKdNo && ! str_starts_with(strtoupper($placementKdNo), 'KN')) {
                $placementKdNo = 'KN'.ltrim($placementKdNo, '-');
            }
        }

        $registration->update([
            'kd_no' => strtoupper(trim($validated['kd_no'])),
            'full_name' => trim($validated['full_name']),
            'gender' => $validated['gender'],
            'state' => trim($validated['state']),
            'full_address' => trim($validated['full_address']),
            'phone_number' => trim($validated['phone_number']),
            'registration_date' => $validated['registration_date'],
            'user_id' => $validated['user_id'] ?? null,
            'sponsor_kd_no' => strtoupper($sponsorKdNo),
            'sponsor_name' => trim($validated['sponsor_name']),
            'placement_kd_no' => $placementKdNo ? strtoupper($placementKdNo) : null,
            'placement_name' => $validated['placement_name'] ? trim($validated['placement_name']) : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Update kd_customers entry
        KdCustomer::updateOrCreate(
            ['kd_no' => $registration->kd_no],
            [
                'customer_name' => $registration->full_name,
                'user_id' => $registration->user_id,
            ]
        );

        return redirect()->route('admin.kd.registration.index')
            ->with('success', 'KD registration updated successfully.');
    }

    /**
     * Remove the specified KD registration.
     */
    public function destroy(KdRegistration $registration)
    {
        $kdNo = $registration->kd_no;
        $registration->delete();

        return redirect()->route('admin.kd.registration.index')
            ->with('success', 'KD registration deleted successfully.');
    }

    /**
     * Add credit/debit transaction to KD registration.
     */
    public function addCredit(Request $request, KdRegistration $registration)
    {
        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $user = $request->user();

        DB::beginTransaction();
        try {
            // Calculate current balance from all existing credits
            $currentBalance = $registration->credits()->sum(DB::raw("CASE WHEN type = 'credit' THEN amount ELSE -amount END"));

            // Calculate new balance
            $amount = (float) $validated['amount'];
            $newBalance = $validated['type'] === KdRegistrationCredit::TYPE_CREDIT
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            // Create credit transaction
            KdRegistrationCredit::create([
                'kd_registration_id' => $registration->id,
                'type' => $validated['type'],
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $validated['reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => $user->id,
            ]);

            DB::commit();

            return redirect()->route('admin.kd.registration.show', $registration)
                ->with('success', ucfirst($validated['type']).' transaction added successfully. New balance: ₦'.number_format($newBalance, 2));
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to add credit transaction: '.$e->getMessage());
        }
    }

    /**
     * Report: Service Centers that currently have Kedi Credit.
     */
    public function creditOwners(Request $request)
    {
        $query = User::query()
            ->with('role')
            ->whereHas('role', function ($q) {
                $q->where('name', Role::SERVICE_CENTER);
            });

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%')
                    ->orWhere('service_center_code', 'like', '%'.$search.'%');
            });
        }

        $min = (float) $request->query('min', 0.01);
        $query->where('kedi_credit_balance', '>=', $min);

        $owners = $query
            ->orderByDesc('kedi_credit_balance')
            ->orderByDesc('created_at')
            ->paginate(50)
            ->withQueryString();

        return view('admin.kd.credit-owners', [
            'owners' => $owners,
            'search' => $request->query('search'),
            'min' => $min,
        ]);
    }

    public function addCreditForServiceCenterForm(Request $request, User $user)
    {
        $user->loadMissing('role');
        abort_unless($user->role?->name === Role::SERVICE_CENTER, 404);

        return view('admin.kd.add-credit-service-center', [
            'serviceCenter' => $user,
            'currentBalance' => (float) ($user->kedi_credit_balance ?? 0),
        ]);
    }

    public function addCreditForServiceCenter(Request $request, User $user)
    {
        $user->loadMissing('role');
        abort_unless($user->role?->name === Role::SERVICE_CENTER, 404);

        $validated = $request->validate([
            'type' => 'required|in:credit,debit',
            'amount' => 'required|numeric|min:0.01',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $amount = (float) $validated['amount'];
            $currentBalance = (float) ($user->fresh()->kedi_credit_balance ?? 0);
            $newBalance = $validated['type'] === KediCreditTransaction::TYPE_CREDIT
                ? $currentBalance + $amount
                : $currentBalance - $amount;

            $user->kedi_credit_balance = $newBalance;
            $user->save();

            KediCreditTransaction::create([
                'user_id' => $user->id,
                'type' => $validated['type'],
                'amount' => $amount,
                'balance_after' => $newBalance,
                'reference' => $validated['reference'] ?? ('Service Center: '.($user->service_center_code ?: $user->email)),
                'notes' => $validated['notes'] ?? null,
                'created_by_user_id' => $request->user()->id,
            ]);

            DB::commit();

            return redirect()
                ->route('admin.kd.registration.index', ['search' => $user->service_center_code ?: $user->email])
                ->with('success', 'Credit updated for '.$user->name.'. New balance: ₦'.number_format($newBalance, 2));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Failed: '.$e->getMessage());
        }
    }
}
