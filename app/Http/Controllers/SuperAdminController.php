<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    public function index(Request $request): View
    {
        return view('admin.index');
    }

    public function showGoLivePrep(): View
    {
        if (! auth()->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $schema = DB::getSchemaBuilder();

        return view('admin.system.go-live', [
            'orderCount' => Order::withTrashed()->count(),
            'invoiceCount' => Invoice::count(),
            'backOrderCount' => $schema->hasTable('back_orders') ? DB::table('back_orders')->count() : 0,
            'walletTxCount' => WalletTransaction::count(),
            'factoryInvoiceCount' => $schema->hasTable('factory_invoices') ? DB::table('factory_invoices')->count() : 0,
        ]);
    }

    public function clearGoLiveData(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'confirm' => ['required', 'in:GO LIVE'],
        ]);

        $this->performGoLiveClear();

        return redirect()
            ->route('admin.system.go-live')
            ->with('success', 'All orders, invoices, back orders, wallet records, and test balances have been cleared. The system is ready for go-live.');
    }

    public function clearOrdersAndWallet(Request $request): RedirectResponse
    {
        if (! $request->user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        $this->performGoLiveClear();

        return back()->with('success', 'All orders, invoices, back orders, factory invoices, and wallet records have been cleared, and wallet balances reset to ₦0.00.');
    }

    private function performGoLiveClear(): void
    {
        DB::transaction(function () {
            $schema = DB::getSchemaBuilder();

            if ($schema->hasTable('kedi_kit_back_orders')) {
                DB::table('kedi_kit_back_orders')->delete();
            }

            if ($schema->hasTable('back_orders')) {
                DB::table('back_orders')->delete();
            }

            if ($schema->hasTable('order_items')) {
                DB::table('order_items')->delete();
            }

            if ($schema->hasTable('orders')) {
                Order::withTrashed()->forceDelete();
            }

            if ($schema->hasTable('invoice_items')) {
                DB::table('invoice_items')->delete();
            }

            if ($schema->hasTable('invoices')) {
                DB::table('invoices')->delete();
            }

            if ($schema->hasTable('factory_invoice_items')) {
                DB::table('factory_invoice_items')->delete();
            }

            if ($schema->hasTable('factory_invoices')) {
                DB::table('factory_invoices')->delete();
            }

            if ($schema->hasTable('wallet_transactions')) {
                DB::table('wallet_transactions')->delete();
            }

            $userUpdates = [];
            if ($schema->hasColumn('users', 'wallet_balance')) {
                $userUpdates['wallet_balance'] = 0;
            }
            if ($schema->hasColumn('users', 'kedi_credit_balance')) {
                $userUpdates['kedi_credit_balance'] = 0;
            }
            if ($userUpdates !== []) {
                User::query()->update($userUpdates);
            }
        });
    }
}
