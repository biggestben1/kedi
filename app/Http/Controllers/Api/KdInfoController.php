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
     * Look up a KD NO in kd_customers / kd_registrations (same as web shop).
     */
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $kdNo = $this->normalizeKdNo((string) $request->input('kd_no', ''));
        if ($kdNo === '') {
            return response()->json(['message' => 'Please enter a KD NO.'], 422);
        }

        $existing = $this->findExistingKd($kdNo);
        if ($existing) {
            return response()->json([
                'found' => true,
                'can_register' => false,
                'kd_no' => $existing['kd_no'],
                'customer_name' => $existing['customer_name'],
                'message' => 'This KEDI number already exists. Using the saved name.',
            ]);
        }

        return response()->json([
            'found' => false,
            'can_register' => true,
            'kd_no' => $kdNo,
            'message' => 'New KD NO. Enter the customer name to register it.',
        ]);
    }

    /**
     * Register a new KD (or reuse existing) for this sales session — same as web store.
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $data = $request->validate([
            'kd_id' => 'required|string|max:100',
            'customer_name' => 'required|string|max:255',
        ], [
            'kd_id.required' => 'Enter a KD NO to start this sale.',
            'customer_name.required' => 'Enter the customer name to register this KD.',
        ]);

        $kdId = $this->normalizeKdNo($data['kd_id']);
        $customerName = trim($data['customer_name']);

        $existing = $this->findExistingKd($kdId);
        if ($existing) {
            $kdId = $existing['kd_no'];
            $customerName = $existing['customer_name'];
        } else {
            KdCustomer::create([
                'kd_no' => $kdId,
                'customer_name' => $customerName,
                'user_id' => $user->id,
            ]);
        }

        return response()->json([
            'success' => true,
            'kd_id' => $kdId,
            'customer_name' => $customerName,
            'message' => $existing
                ? 'Sales session started with existing KEDI.'
                : 'Registered and sales session started.',
            'registered' => ! $existing,
        ]);
    }

    /**
     * @deprecated Kept for older app builds; prefer search + store.
     */
    public function autoGenerate(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $baseKd = 'KD-'.$user->id.'-';
        $existing = KdCustomer::where('kd_no', 'like', $baseKd.'%')->max('kd_no');
        $seq = 1;
        if ($existing) {
            $parts = explode('-', $existing);
            $seq = (int) (end($parts) ?: 0) + 1;
        }
        $kdNo = $baseKd.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
        $customerName = trim($user->name ?? $user->email ?? 'Customer');

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
     * @return array{kd_no: string, customer_name: string}|null
     */
    private function findExistingKd(string $kdNo): ?array
    {
        if ($kdNo === '') {
            return null;
        }

        $customer = KdCustomer::query()
            ->whereRaw("UPPER(REPLACE(kd_no, ' ', '')) = ?", [$kdNo])
            ->first();

        if ($customer && trim((string) $customer->customer_name) !== '') {
            return [
                'kd_no' => strtoupper(trim($customer->kd_no)),
                'customer_name' => trim($customer->customer_name),
            ];
        }

        $registration = KdRegistration::query()
            ->whereRaw("UPPER(REPLACE(kd_no, ' ', '')) = ?", [$kdNo])
            ->first();

        if ($registration && trim((string) $registration->full_name) !== '') {
            return [
                'kd_no' => strtoupper(trim($registration->kd_no)),
                'customer_name' => trim($registration->full_name),
            ];
        }

        return null;
    }

    private function normalizeKdNo(string $kdNo): string
    {
        $kdNo = strtoupper(trim($kdNo));
        $kdNo = preg_replace('/\s+/', '', $kdNo) ?? $kdNo;

        return $kdNo;
    }
}
