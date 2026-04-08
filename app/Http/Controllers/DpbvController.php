<?php

namespace App\Http\Controllers;

use App\Models\DpbvCollection;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DpbvController extends Controller
{
    private function serviceCenterCodeVariants(User $user): array
    {
        $serviceCenterCode = trim((string) ($user->service_center_code ?? ''));
        if (($user->role?->name ?? null) !== 'service_center' || $serviceCenterCode === '') {
            return [];
        }

        return array_values(array_unique([
            $serviceCenterCode,
            strtoupper($serviceCenterCode),
            strtolower($serviceCenterCode),
        ]));
    }

    private function canAccessDpbvRecord(User $user, DpbvCollection $record): bool
    {
        if ((int) $record->user_id === (int) $user->id) {
            return true;
        }

        if ($record->user_id === null) {
            $scVariants = $this->serviceCenterCodeVariants($user);

            return ! empty($scVariants) && in_array((string) $record->sc, $scVariants, true);
        }

        return false;
    }

    private function userDpbvQuery(User $user)
    {
        $query = DpbvCollection::query()->where('user_id', $user->id);

        // Backward-compatibility for older imports where user_id was not assigned:
        // allow Service Center users to see rows matched by their SC code.
        $scVariants = $this->serviceCenterCodeVariants($user);
        if (! empty($scVariants)) {
            $query->orWhere(function ($q) use ($scVariants) {
                $q->whereNull('user_id')
                    ->whereIn('sc', $scVariants);
            });
        }

        return $query;
    }

    /**
     * Show the current user's DPBV records.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $query = $this->userDpbvQuery($user);
        $search = trim((string) $request->query('search', ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%'.$search.'%')
                    ->orWhere('name', 'like', '%'.$search.'%')
                    ->orWhere('sc', 'like', '%'.$search.'%')
                    ->orWhereDate('record_date', $search);
            });
        }

        // Visible collections: only non-negative DPBV rows (hide transfer-out/spending debits).
        // We still calculate totals/net per KD NO using all ledger rows.
        $visibleQuery = (clone $query)->where('dpbv', '>=', 0);

        $collections = $visibleQuery
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(30)
            ->appends($request->query());

        $totalDpbv = (clone $query)->sum('dpbv');
        // Calculate naira equivalent: (Total DPBV * 0.95) * 990
        $nairaEquivalent = ($totalDpbv * 0.95) * 990;
        $cartCount = array_sum($request->session()->get('cart', []));

        // Net DPBV per KD NO for the visible page:
        // this makes codes transferred out not appear "twice" (positive import + negative transfer-out).
        $codesOnPage = $collections->pluck('code')->filter()->unique()->values()->all();
        $netByCode = [];
        if (! empty($codesOnPage)) {
            $netByCode = (clone $query)
                ->whereIn('code', $codesOnPage)
                ->select('code', DB::raw('SUM(dpbv) as net_dpbv'))
                ->groupBy('code')
                ->pluck('net_dpbv', 'code')
                ->mapWithKeys(function ($v, $k) {
                    return [$k => (float) $v];
                })
                ->toArray();
        }

        $isAjax = $request->ajax()
            || $request->expectsJson()
            || strtolower((string) $request->header('X-Requested-With')) === 'xmlhttprequest';

        if ($isAjax) {
            return response()->json([
                'html' => view('dpbv.partials.table', [
                    'collections' => $collections,
                    'netByCode' => $netByCode,
                ])->render(),
                'totalDpbv' => (float) $totalDpbv,
                'nairaEquivalent' => (float) $nairaEquivalent,
            ]);
        }

        return view('dpbv.index', [
            'collections' => $collections,
            'netByCode' => $netByCode,
            'totalDpbv' => $totalDpbv,
            'nairaEquivalent' => $nairaEquivalent,
            'cartCount' => $cartCount,
            'search' => $search,
        ]);
    }

    public function transfer(Request $request)
    {
        $sender = $request->user();
        $data = $request->validate([
            'source_id' => ['required', 'integer', 'exists:dpbv_collections,id'],
            'recipient_email' => ['required', 'email'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'code' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
        ]);

        $recipient = User::where('email', $data['recipient_email'])->first();
        if (! $recipient) {
            return back()->withErrors(['recipient_email' => 'Recipient email not found.'])->withInput();
        }

        if ((int) $recipient->id === (int) $sender->id) {
            return back()->withErrors(['recipient_email' => 'You cannot transfer DPBV to yourself.'])->withInput();
        }

        $amount = round((float) $data['amount'], 2);
        $source = DpbvCollection::findOrFail((int) $data['source_id']);
        if (! $this->canAccessDpbvRecord($sender, $source)) {
            return back()->withErrors(['amount' => 'Selected DPBV record is not available for transfer.'])->withInput();
        }
        if ((float) $source->dpbv <= 0) {
            return back()->withErrors(['amount' => 'Only positive DPBV entries can be transferred.'])->withInput();
        }
        if ($amount > (float) $source->dpbv) {
            return back()->withErrors(['amount' => 'Amount cannot be greater than selected row DPBV.'])->withInput();
        }

        $available = (float) ((clone $this->userDpbvQuery($sender))->sum('dpbv'));
        if ($amount > $available) {
            return back()->withErrors(['amount' => 'Insufficient DPBV balance for this transfer.'])->withInput();
        }

        DB::transaction(function () use ($sender, $recipient, $amount, $data) {
            $today = now()->toDateString();

            DpbvCollection::create([
                'no' => null,
                'code' => $data['code'],
                'name' => $data['name'],
                'record_date' => $today,
                'sc' => 'TRANSFER_OUT:'.$recipient->email,
                'dpbv' => -$amount,
                'user_id' => $sender->id,
            ]);

            DpbvCollection::create([
                'no' => null,
                'code' => $data['code'],
                'name' => $data['name'],
                'record_date' => $today,
                'sc' => 'TRANSFER_IN:'.$sender->email,
                'dpbv' => $amount,
                'user_id' => $recipient->id,
            ]);
        });

        return back()->with('success', 'DPBV transferred successfully to '.$recipient->email.'.');
    }

    public function checkRecipientEmail(Request $request)
    {
        $sender = $request->user();
        $email = trim((string) $request->query('email', ''));

        if ($email === '') {
            return response()->json([
                'exists' => false,
                'message' => 'Enter recipient email.',
            ]);
        }

        $recipient = User::where('email', $email)->first();
        if (! $recipient) {
            return response()->json([
                'exists' => false,
                'message' => 'Recipient email not found.',
            ]);
        }

        if ((int) $recipient->id === (int) $sender->id) {
            return response()->json([
                'exists' => false,
                'message' => 'You cannot transfer to yourself.',
            ]);
        }

        return response()->json([
            'exists' => true,
            'name' => $recipient->name,
            'email' => $recipient->email,
            'message' => 'Recipient found.',
        ]);
    }

    /**
     * Show DPBV spending history (negative DPBV entries and orders).
     */
    public function spending(Request $request)
    {
        $user = $request->user();

        // Get negative DPBV entries (spending)
        $spending = DpbvCollection::where('user_id', $user->id)
            ->where('dpbv', '<', 0)
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->paginate(30);

        // Get orders paid with DPBV
        $dpbvOrders = Order::where('user_id', $user->id)
            ->where(function ($query) {
                $query->where('payment_method', Order::PAYMENT_DPBV)
                    ->orWhere('is_dpbv_order', true);
            })
            ->with('items')
            ->orderByDesc('created_at')
            ->get();

        $totalSpent = abs((float) DpbvCollection::where('user_id', $user->id)->where('dpbv', '<', 0)->sum('dpbv'));
        $totalSpentNaira = ($totalSpent * 0.95) * 990;
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('dpbv.spending', [
            'spending' => $spending,
            'dpbvOrders' => $dpbvOrders,
            'totalSpent' => $totalSpent,
            'totalSpentNaira' => $totalSpentNaira,
            'cartCount' => $cartCount,
        ]);
    }
}
