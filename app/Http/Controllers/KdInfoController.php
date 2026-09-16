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
            'kd_id' => 'required|string|max:100',
            'customer_name' => 'required|string|max:255',
        ], [
            'kd_id.required' => 'Enter a KD NO to start this sale.',
            'customer_name.required' => 'Enter the customer name to register this KD and start the sale.',
        ]);

        $kdId = $this->normalizeKdNo((string) $request->input('kd_id', ''));
        $customerName = trim((string) $request->input('customer_name', ''));

        $existing = $this->findExistingKd($kdId);
        if ($existing) {
            // Already registered — leave the record unchanged and use it as the sales session.
            $kdId = $existing['kd_no'];
            $customerName = $existing['customer_name'];
        } else {
            KdCustomer::create([
                'kd_no' => $kdId,
                'customer_name' => $customerName,
                'user_id' => $request->user()?->id,
            ]);
        }

        $this->startSalesSession($request, $kdId, $customerName);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'kd_id' => $kdId,
                'customer_name' => $customerName,
                'message' => 'Registered and sales session started.',
            ]);
        }

        $message = 'Registered KEDI '.$kdId.' — '.$customerName.'.';
        $redirectTo = trim((string) $request->input('redirect_to', ''));
        if ($redirectTo !== '' && str_starts_with($redirectTo, url('/'))) {
            return redirect()->to($redirectTo)->with('success', $message);
        }

        return redirect()->route('shop')->with('success', 'Sales session started for '.$kdId.' — '.$customerName.'.');
    }

    /** If the KEDI number already exists, start a sales session without changing it. */
    public function useExisting(Request $request)
    {
        if (! $request->user()) {
            return response()->json(['error' => 'You must be logged in.'], 403);
        }

        $kdNo = $this->normalizeKdNo((string) $request->input('kd_no', ''));
        $existing = $this->findExistingKd($kdNo);
        if (! $existing) {
            return response()->json([
                'found' => false,
                'can_register' => true,
                'message' => 'New KD NO. Enter the customer name to register it.',
            ]);
        }

        $this->startSalesSession($request, $existing['kd_no'], $existing['customer_name']);

        return response()->json([
            'found' => true,
            'session_started' => true,
            'kd_no' => $existing['kd_no'],
            'customer_name' => $existing['customer_name'],
            'message' => 'This KEDI number already exists. Sales session started with the saved name.',
        ]);
    }

    /** End the current sales session so another KD can be registered. */
    public function clear(Request $request)
    {
        $request->session()->forget(['kd_id', 'customer_name']);

        $hasGroup = (int) $request->session()->get('order_group_id', 0) > 0;
        $message = $hasGroup
            ? 'KEDI transaction closed. Enter the next KEDI NO and name to add another order to the group.'
            : 'Sales session ended. Enter a KD NO and name to start another sale.';

        return redirect()->route('shop')->with('message', $message);
    }

    /**
     * Look up a KD NO. Unknown numbers can be registered with a name on this page.
     */
    public function search(Request $request)
    {
        if (! $request->user()) {
            return response()->json(['error' => 'You must be logged in.'], 403);
        }

        $kdNo = $this->normalizeKdNo((string) $request->input('kd_no', ''));
        if ($kdNo === '') {
            return response()->json(['error' => 'Please enter a KD NO.'], 400);
        }

        $existing = $this->findExistingKd($kdNo);
        if ($existing) {
            return response()->json([
                'found' => true,
                'registered' => true,
                'kd_no' => $existing['kd_no'],
                'customer_name' => $existing['customer_name'],
                'message' => 'This KEDI number already exists. Using the saved name for this sales session.',
            ]);
        }

        return response()->json([
            'found' => false,
            'can_register' => true,
            'kd_no' => $kdNo,
            'message' => 'New KD NO. Enter the customer name below to register it and start this sale.',
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

    private function startSalesSession(Request $request, string $kdId, string $customerName): void
    {
        $request->session()->put('kd_id', $kdId);
        $request->session()->put('customer_name', $customerName);

        $user = $request->user();
        if ($user) {
            Order::where('user_id', $user->id)
                ->whereNull('kd_id')
                ->update(['kd_id' => $kdId, 'customer_name' => $customerName]);
        }
    }

    private function normalizeKdNo(string $kdNo): string
    {
        $kdNo = strtoupper(trim($kdNo));
        $kdNo = preg_replace('/\s+/', '', $kdNo) ?? $kdNo;

        return $kdNo;
    }
}
