<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\KediKitItem;
use App\Models\KediKitPurchase;
use App\Models\KdRegistrationCredit;
use App\Models\Role;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class KdRegistrationController extends Controller
{
    /**
     * Display a listing of KD registrations.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $roleName = $user->role->name ?? '';
        
        $query = KdRegistration::with(['user', 'registeredBy']);

        // Super Admin and Super Admin Accountant can see all registrations
        // Other users can only see registrations they created
        $isSuperAdmin = $roleName === 'super_admin';
        
        // Check if accountant was created by Super Admin
        $isSuperAdminAccountant = false;
        if ($roleName === 'accountant' && $user->created_by_user_id) {
            $createdBy = User::with('role')->find($user->created_by_user_id);
            $isSuperAdminAccountant = $createdBy && $createdBy->role && $createdBy->role->name === 'super_admin';
        }
        
        if (!$isSuperAdmin && !$isSuperAdminAccountant) {
            // Filter to show only registrations created by this user
            $query->where('registered_by_user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->query('search');
            $query->where(function ($q) use ($search) {
                $q->where('kd_no', 'like', '%' . $search . '%')
                    ->orWhere('full_name', 'like', '%' . $search . '%')
                    ->orWhere('phone_number', 'like', '%' . $search . '%')
                    ->orWhere('sponsor_kd_no', 'like', '%' . $search . '%')
                    ->orWhere('sponsor_name', 'like', '%' . $search . '%');
            });
        }

        $registrations = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        return view('admin.kd.registration.index', [
            'registrations' => $registrations,
            'search' => $request->query('search'),
        ]);
    }

    /**
     * Show the form for creating a new KD registration.
     */
    public function create()
    {
        $user = auth()->user();
        $walletBalance = $user->wallet_balance ?? 0;
        $users = User::with('role')->orderBy('name')->get();
        return view('admin.kd.registration.create', [
            'users' => $users,
            'walletBalance' => $walletBalance,
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
        ]);

        $user = $request->user();
        $registrationFee = $request->has('from_kit') ? 0.00 : 12000.00;

        // Check wallet balance
        if (!$user->canPayWithWallet($registrationFee)) {
            return back()->withInput()
                ->with('error', 'Insufficient wallet balance. Your balance is ₦' . number_format($user->wallet_balance ?? 0, 2) . ' but you need ₦' . number_format($registrationFee, 2) . ' for registration.');
        }

        DB::beginTransaction();
        try {

        // Format sponsor and placement KD NO (ensure KN prefix)
        $sponsorKdNo = trim($validated['sponsor_kd_no']);
        if ($sponsorKdNo && !str_starts_with(strtoupper($sponsorKdNo), 'KN')) {
            $sponsorKdNo = 'KN' . ltrim($sponsorKdNo, '-');
        }
        
        $placementKdNo = null;
        if ($validated['placement_kd_no']) {
            $placementKdNo = trim($validated['placement_kd_no']);
            if ($placementKdNo && !str_starts_with(strtoupper($placementKdNo), 'KN')) {
                $placementKdNo = 'KN' . ltrim($placementKdNo, '-');
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

            // Deduct registration fee from wallet if applicable
            if ($registrationFee > 0) {
                $user->decrement('wallet_balance', $registrationFee);
            }
            $balanceAfter = (float) $user->fresh()->wallet_balance;

            // Create wallet transaction if a fee was paid
            if ($registrationFee > 0) {
                WalletTransaction::create([
                    'user_id' => $user->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $registrationFee,
                    'balance_after' => $balanceAfter,
                    'reference' => 'KD Registration Fee - KD NO: ' . $registration->kd_no,
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
                    if (!$kitItem) {
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
                                'purchased_by_user_id' => $purchase->buyer_user_id
                            ]);
                        }
                    }
                    
                    if ($kitItem->wasRecentlyCreated || !$kitItem->purchased_by_user_id || !$kitItem->kedi_kit_purchase_id) {
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
                    ->with('success', 'KD registration created successfully. KD NO: ' . $registration->kd_no . '. ₦' . number_format($registrationFee, 2) . ' deducted from your wallet.');
            }

            return redirect()->route('admin.kd.registration.index')
                ->with('success', 'KD registration created successfully. KD NO: ' . $registration->kd_no . '. ₦' . number_format($registrationFee, 2) . ' deducted from your wallet.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to create KD registration: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified KD registration.
     */
    public function show(KdRegistration $registration)
    {
        $registration->load(['user', 'registeredBy', 'credits.createdBy']);
        
        // Calculate current credit balance from all transactions
        $credits = $registration->credits()->with('createdBy')->orderByDesc('created_at')->get();
        $creditBalance = $credits->sum(function($credit) {
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
            'kd_no' => 'required|string|max:100|unique:kd_registrations,kd_no,' . $registration->id,
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
        if ($sponsorKdNo && !str_starts_with(strtoupper($sponsorKdNo), 'KN')) {
            $sponsorKdNo = 'KN' . ltrim($sponsorKdNo, '-');
        }
        
        $placementKdNo = null;
        if ($validated['placement_kd_no']) {
            $placementKdNo = trim($validated['placement_kd_no']);
            if ($placementKdNo && !str_starts_with(strtoupper($placementKdNo), 'KN')) {
                $placementKdNo = 'KN' . ltrim($placementKdNo, '-');
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
                ->with('success', ucfirst($validated['type']) . ' transaction added successfully. New balance: ₦' . number_format($newBalance, 2));
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()
                ->with('error', 'Failed to add credit transaction: ' . $e->getMessage());
        }
    }
}
