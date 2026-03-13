<?php

namespace App\Http\Controllers;

use App\Models\KdCustomer;
use App\Models\KdRegistration;
use App\Models\Order;
use Illuminate\Http\Request;

class KdInfoController extends Controller
{
    /** Auto-generate KD NO for the current user and save to kd_customers; user can edit later. */
    public function autoGenerate(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'You must be logged in.'], 403);
        }

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

        $request->session()->put('kd_id', $kdNo);
        $request->session()->put('customer_name', $kd->customer_name);

        Order::where('user_id', $user->id)
            ->whereNull('kd_id')
            ->update(['kd_id' => $kdNo, 'customer_name' => $kd->customer_name]);

        return response()->json([
            'success' => true,
            'kd_id' => $kdNo,
            'customer_name' => $kd->customer_name,
            'message' => 'KD NO generated. You can edit and save changes if needed.',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'kd_id' => 'nullable|string|max:100',
            'customer_name' => 'nullable|string|max:255',
        ]);

        $kdId = trim((string) $request->input('kd_id', ''));
        $customerName = trim((string) $request->input('customer_name', ''));

        $request->session()->put('kd_id', $kdId);
        $request->session()->put('customer_name', $customerName);

        if ($kdId !== '' && $customerName !== '') {
            // Save to kd_customers table (canonical KD list)
            KdCustomer::updateOrCreate(
                ['kd_no' => $kdId],
                ['customer_name' => $customerName, 'user_id' => $request->user()?->id]
            );

            // Transfer guest orders (orders with null kd_id) to this KD
            $user = $request->user();
            if ($user) {
                Order::where('user_id', $user->id)
                    ->whereNull('kd_id')
                    ->update(['kd_id' => $kdId, 'customer_name' => $customerName]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'KD info saved.']);
        }

        return back()->with('success', $kdId !== '' && $customerName !== '' ? 'KD info saved. You can now shop.' : 'You can browse and add items. Add KD info later when you have it.');
    }

    /**
     * Search for KD NO in the system and check if it belongs to the logged-in user
     */
    public function search(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'You must be logged in.'], 403);
        }

        $kdNo = trim((string) $request->input('kd_no', ''));
        if (empty($kdNo)) {
            return response()->json(['error' => 'Please enter a KD NO.'], 400);
        }

        // Normalize KD NO (uppercase, ensure KN prefix if needed)
        $kdNo = strtoupper($kdNo);
        if (!str_starts_with($kdNo, 'KN') && !str_starts_with($kdNo, 'KD')) {
            // Try both KN and KD prefixes
            $kdNoKn = 'KN' . ltrim($kdNo, '-');
            $kdNoKd = 'KD' . ltrim($kdNo, '-');
        } else {
            $kdNoKn = $kdNo;
            $kdNoKd = $kdNo;
        }

        // Search in kd_registrations table
        $registration = KdRegistration::where(function($query) use ($kdNoKn, $kdNoKd) {
            $query->where('kd_no', $kdNoKn)
                  ->orWhere('kd_no', $kdNoKd);
        })->first();

        if (!$registration) {
            return response()->json([
                'found' => false,
                'message' => 'KD NO not found in the system.',
            ]);
        }

        // Check if it belongs to the logged-in user
        $belongsToUser = false;
        if ($registration->user_id == $user->id || $registration->registered_by_user_id == $user->id) {
            $belongsToUser = true;
        }

        if (!$belongsToUser) {
            return response()->json([
                'found' => true,
                'belongs_to_user' => false,
                'message' => 'KD NO found but does not belong to your account.',
            ]);
        }

        // Auto-fill customer name from database
        return response()->json([
            'found' => true,
            'belongs_to_user' => true,
            'kd_no' => $registration->kd_no,
            'customer_name' => $registration->full_name, // This is the name from kd_registrations table
            'message' => 'KD NO found and belongs to you. Customer name auto-filled from database.',
        ]);
    }
}
