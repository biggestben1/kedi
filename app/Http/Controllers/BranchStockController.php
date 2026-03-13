<?php

namespace App\Http\Controllers;

use App\Models\BranchStock;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class BranchStockController extends Controller
{
    /** Branch: view own stock. Super Admin: view all branches, allocate stock. */
    public function index(Request $request)
    {
        $user = $request->user();
        $isSuperAdmin = $user->role?->name === 'super_admin';
        $branchUserId = $request->query('branch_user_id');

        $branchUser = null;
        if ($isSuperAdmin && $branchUserId) {
            $branchUser = User::with('role')->find($branchUserId);
            if (! $branchUser || $branchUser->role?->name !== 'branch') {
                return redirect()->route('admin.branch.stock.index')->with('error', 'Invalid branch.');
            }
            $branchUserId = (int) $branchUserId;
        } elseif ($user->role?->name === 'branch') {
            $branchUserId = $user->id;
            $branchUser = $user->load('role');
        } elseif (! $isSuperAdmin) {
            return redirect()->route('admin')->with('error', 'Access denied.');
        }

        $stockItems = BranchStock::with('product')
            ->where('branch_user_id', $branchUserId)
            ->where('quantity', '>', 0)
            ->orderBy('product_id')
            ->get();

        $branchUsers = $isSuperAdmin
            ? User::whereHas('role', fn ($r) => $r->where('name', 'branch'))->orderBy('name')->get()
            : collect();

        return view('admin.branch.stock.index', [
            'stockItems' => $stockItems,
            'branchUser' => $branchUser,
            'branchUsers' => $branchUsers,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    /** Super Admin: allocate stock from main warehouse to branch. */
    public function allocate(Request $request)
    {
        if ($request->user()?->role?->name !== 'super_admin') {
            abort(403, 'Only Super Admin can allocate stock to branches.');
        }

        $branchUsers = User::whereHas('role', fn ($r) => $r->where('name', 'branch'))->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.branch.stock.allocate', [
            'branchUsers' => $branchUsers,
            'products' => $products,
        ]);
    }

    public function storeAllocate(Request $request)
    {
        if ($request->user()?->role?->name !== 'super_admin') {
            abort(403, 'Only Super Admin can allocate stock to branches.');
        }

        $data = $request->validate([
            'branch_user_id' => ['required', 'integer', 'exists:users,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $branchUser = User::whereHas('role', fn ($r) => $r->where('name', 'branch'))->find($data['branch_user_id']);
        if (! $branchUser) {
            return redirect()->back()->with('error', 'Invalid branch.');
        }

        $product = Product::findOrFail($data['product_id']);
        if ((int) $product->stock < $data['quantity']) {
            return redirect()->back()->withInput()->with('error', "Insufficient main stock. Available: {$product->stock}");
        }

        $product->decrement('stock', $data['quantity']);

        $bs = BranchStock::firstOrCreate(
            [
                'branch_user_id' => $data['branch_user_id'],
                'product_id' => $data['product_id'],
            ],
            ['quantity' => 0]
        );
        $bs->increment('quantity', $data['quantity']);

        return redirect()->route('admin.branch.stock.index', ['branch_user_id' => $data['branch_user_id']])
            ->with('success', 'Stock allocated successfully.');
    }
}
