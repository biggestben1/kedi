<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\Order;
use App\Models\OrderGroup;
use App\Models\OrderItem;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KdRegistrationController extends Controller
{
    public const REGISTRATION_FEE = 12000.00;

    public function createForm(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->loadMissing(['role', 'createdBy.role']);
        $walletOwner = $user->walletOwnerForShopping();

        $openOrderGroups = OrderGroup::where('user_id', $user->id)
            ->where('status', OrderGroup::STATUS_OPEN)
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (OrderGroup $group) => [
                'id' => $group->id,
                'name' => $group->displayName(),
                'draft_total' => $group->draftTotal(),
            ]);

        return response()->json([
            'registration_fee' => self::REGISTRATION_FEE,
            'wallet_balance' => (float) ($walletOwner->wallet_balance ?? 0),
            'wallet_owner_id' => $walletOwner->id,
            'linked_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->display_name ?? $user->role?->name,
            ],
            'open_order_groups' => $openOrderGroups,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kd_no' => 'required|string|max:100|unique:kd_registrations,kd_no',
            'full_name' => 'required|string|max:255',
            'phone_number' => 'nullable|string|max:50',
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
        $walletOwner = $user->walletOwnerForShopping();
        $registrationType = $request->input('registration_type', 'new');
        $addToOrderGroup = $request->boolean('add_to_order_group');
        $registrationFee = $registrationType === 'old' ? 0.00 : self::REGISTRATION_FEE;

        $orderGroup = null;
        if ($addToOrderGroup) {
            $groupId = (int) ($validated['order_group_id'] ?? 0);
            $orderGroup = OrderGroup::where('id', $groupId)
                ->where('user_id', $user->id)
                ->where('status', OrderGroup::STATUS_OPEN)
                ->first();

            if (! $orderGroup) {
                return response()->json([
                    'message' => 'No open order group selected. Create a group first, then add the registration fee to it.',
                ], 422);
            }

            $registrationFee = $registrationType === 'old' ? 0.00 : self::REGISTRATION_FEE;
        }

        if (! $addToOrderGroup && $registrationFee > 0 && ! $walletOwner->canPayWithWallet($registrationFee)) {
            return response()->json([
                'message' => 'Insufficient wallet balance. Your balance is ₦'.number_format($walletOwner->wallet_balance ?? 0, 2).' but you need ₦'.number_format($registrationFee, 2).' for registration.',
                'wallet_balance' => (float) ($walletOwner->wallet_balance ?? 0),
                'registration_fee' => $registrationFee,
            ], 422);
        }

        try {
            $registration = DB::transaction(function () use (
                $validated,
                $user,
                $walletOwner,
                $addToOrderGroup,
                $orderGroup,
                $registrationFee
            ) {
                $sponsorKdNo = trim($validated['sponsor_kd_no']);
                if ($sponsorKdNo && ! str_starts_with(strtoupper($sponsorKdNo), 'KN')) {
                    $sponsorKdNo = 'KN'.ltrim($sponsorKdNo, '-');
                }

                $placementKdNo = null;
                if (! empty($validated['placement_kd_no'])) {
                    $placementKdNo = trim($validated['placement_kd_no']);
                    if ($placementKdNo && ! str_starts_with(strtoupper($placementKdNo), 'KN')) {
                        $placementKdNo = 'KN'.ltrim($placementKdNo, '-');
                    }
                }

                $registration = KdRegistration::create([
                    'kd_no' => strtoupper(trim($validated['kd_no'])),
                    'full_name' => trim($validated['full_name']),
                    'gender' => null,
                    'state' => null,
                    'full_address' => null,
                    'phone_number' => isset($validated['phone_number']) && trim($validated['phone_number']) !== ''
                        ? trim($validated['phone_number'])
                        : null,
                    'registration_date' => now()->toDateString(),
                    'user_id' => $user->id,
                    'sponsor_kd_no' => strtoupper($sponsorKdNo),
                    'sponsor_name' => trim($validated['sponsor_name']),
                    'placement_kd_no' => $placementKdNo ? strtoupper($placementKdNo) : null,
                    'placement_name' => ! empty($validated['placement_name']) ? trim($validated['placement_name']) : null,
                    'registered_by_user_id' => $user->id,
                    'notes' => $validated['notes'] ?? null,
                ]);

                KdCustomer::updateOrCreate(
                    ['kd_no' => $registration->kd_no],
                    [
                        'customer_name' => $registration->full_name,
                        'user_id' => $registration->user_id,
                    ]
                );

                if ($addToOrderGroup && $orderGroup && $registrationFee > 0) {
                    $this->attachRegistrationFeeToGroup($user, $orderGroup, $registration, $registrationFee);
                } elseif (! $addToOrderGroup && $registrationFee > 0) {
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

                return $registration;
            });
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to create KD registration: '.$e->getMessage(),
            ], 500);
        }

        $message = 'KD registration created. KD NO: '.$registration->kd_no.'.';
        if ($addToOrderGroup && $orderGroup) {
            $message .= $registrationFee > 0
                ? ' ₦'.number_format($registrationFee, 2).' fee added to group "'.$orderGroup->displayName().'". Pay all when ready.'
                : ' No fee added (old registration).';
        } else {
            $message .= ' ₦'.number_format($registrationFee, 2).' deducted from wallet.';
        }

        return response()->json([
            'message' => $message,
            'data' => [
                'id' => $registration->id,
                'kd_no' => $registration->kd_no,
                'full_name' => $registration->full_name,
                'phone_number' => $registration->phone_number,
                'registration_date' => $registration->registration_date?->toDateString(),
                'sponsor_kd_no' => $registration->sponsor_kd_no,
                'sponsor_name' => $registration->sponsor_name,
                'placement_kd_no' => $registration->placement_kd_no,
                'placement_name' => $registration->placement_name,
                'fee_charged' => $registrationFee,
                'added_to_order_group_id' => $addToOrderGroup ? $orderGroup?->id : null,
                'wallet_balance' => (float) ($walletOwner->fresh()->wallet_balance ?? 0),
            ],
        ], 201);
    }

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
}
