<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.index');
    }

    public function clearOrdersAndWallet(Request $request)
    {
        if (! $request->user() || ! $request->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }

        DB::transaction(function () {
            $schema = DB::getSchemaBuilder();

            // Delete customer order-related data
            if ($schema->hasTable('order_items')) {
                DB::table('order_items')->delete();
            }

            if ($schema->hasTable('orders')) {
                DB::table('orders')->delete();
            }

            // Delete sales invoices and related data
            if ($schema->hasTable('invoice_items')) {
                DB::table('invoice_items')->delete();
            }

            if ($schema->hasTable('back_orders')) {
                DB::table('back_orders')->delete();
            }

            if ($schema->hasTable('invoices')) {
                DB::table('invoices')->delete();
            }

            // Delete factory invoices (stock in) and their items (optional, for full reset)
            if ($schema->hasTable('factory_invoice_items')) {
                DB::table('factory_invoice_items')->delete();
            }

            if ($schema->hasTable('factory_invoices')) {
                DB::table('factory_invoices')->delete();
            }

            // Delete wallet transactions
            if ($schema->hasTable('wallet_transactions')) {
                DB::table('wallet_transactions')->delete();
            }

            // Reset all user wallet balances
            if ($schema->hasColumn('users', 'wallet_balance')) {
                User::query()->update(['wallet_balance' => 0]);
            }
        });

        return back()->with('success', 'All orders, invoices, back orders, factory invoices, and wallet records have been cleared, and wallet balances reset to ₦0.00.');
    }
}
