<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KdCustomer;
use App\Models\KdRegistration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KdInfoController extends Controller
{
    /**
     * Auto-generate KD NO for the current user and return it.
     */
    public function autoGenerate(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
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

        // Note: For API, we just return the suggestion. The client will decide to save it/use it.
        // Or we can save it to kd_customers like the web version does.
        KdCustomer::updateOrCreate(
            ['kd_no' => $kdNo],
            ['customer_name' => $customerName, 'user_id' => $user->id]
        );

        return response()->json([
            'success' => true,
            'kd_id' => $kdNo,
            'customer_name' => $customerName,
            'message' => 'KD NO generated successfully.',
        ]);
    }

    /**
     * Search for KD NO in the system.
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $kdNo = trim((string) $request->input('kd_no', ''));
        if (empty($kdNo)) {
            return response()->json(['message' => 'Please enter a KD NO.'], 422);
        }

        // Normalize KD NO
        $kdNo = strtoupper($kdNo);
        if (!str_starts_with($kdNo, 'KN') && !str_starts_with($kdNo, 'KD')) {
            $kdNoKn = 'KN' . ltrim($kdNo, '-');
            $kdNoKd = 'KD' . ltrim($kdNo, '-');
        } else {
            $kdNoKn = $kdNo;
            $kdNoKd = $kdNo;
        }

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
        $belongsToUser = ($registration->user_id == $user->id || $registration->registered_by_user_id == $user->id);

        if (!$belongsToUser) {
            return response()->json([
                'found' => true,
                'belongs_to_user' => false,
                'message' => 'KD NO found but does not belong to your account.',
            ]);
        }

        return response()->json([
            'found' => true,
            'belongs_to_user' => true,
            'kd_no' => $registration->kd_no,
            'customer_name' => $registration->full_name,
            'message' => 'KD NO found and belongs to you.',
        ]);
    }
}
