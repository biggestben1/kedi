<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminPurchaseController extends Controller
{
    public function index()
    {
        $purchases = Purchase::with('supplier', 'items.product')
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.purchases.index', ['purchases' => $purchases]);
    }

    public function create()
    {
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get(['id', 'item_code', 'name', 'cost_price']);
        return view('admin.purchases.create', [
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_invoice' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['required', 'string', 'in:pending,paid,partial'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase = DB::transaction(function () use ($data) {
            $purchase = Purchase::create([
                'supplier_id' => $data['supplier_id'],
                'purchase_date' => $data['purchase_date'],
                'purchase_invoice' => $data['purchase_invoice'] ?? null,
                'payment_status' => $data['payment_status'],
            ]);

            foreach ($data['items'] as $row) {
                $product = Product::find($row['product_id']);
                $qty = (int) $row['quantity'];
                $cost = (float) $row['cost_price'];
                $lineTotal = $qty * $cost;
                PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'item_code' => $product->item_code,
                    'product_name' => $product->name . ($product->pack_size ? " ({$product->pack_size})" : ''),
                    'quantity' => $qty,
                    'cost_price' => $cost,
                    'line_total' => $lineTotal,
                ]);
                $product->increment('stock', $qty);
            }

            return $purchase;
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase invoice created. Stock has been added to products.');
    }

    public function edit(Purchase $purchase)
    {
        $purchase->load('supplier', 'items.product');
        $suppliers = Supplier::orderBy('name')->get();
        $products = Product::orderBy('name')->get(['id', 'item_code', 'name', 'cost_price']);
        return view('admin.purchases.edit', [
            'purchase' => $purchase,
            'suppliers' => $suppliers,
            'products' => $products,
        ]);
    }

    public function update(Request $request, Purchase $purchase)
    {
        $data = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'purchase_invoice' => ['nullable', 'string', 'max:100'],
            'payment_status' => ['required', 'string', 'in:pending,paid,partial'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:purchase_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.cost_price' => ['required', 'numeric', 'min:0'],
        ]);

        $purchase->update([
            'supplier_id' => $data['supplier_id'],
            'purchase_date' => $data['purchase_date'],
            'purchase_invoice' => $data['purchase_invoice'] ?? null,
            'payment_status' => $data['payment_status'],
        ]);

        $keepIds = [];
        DB::transaction(function () use ($data, $purchase, &$keepIds) {
            foreach ($data['items'] as $row) {
                $product = Product::find($row['product_id']);
                $qty = (int) $row['quantity'];
                $cost = (float) $row['cost_price'];
                $lineTotal = $qty * $cost;
                if (!empty($row['id'])) {
                    $item = PurchaseItem::where('purchase_id', $purchase->id)->find($row['id']);
                    if ($item) {
                        $oldQty = (int) $item->quantity;
                        if ($item->product_id === $product->id && $qty !== $oldQty) {
                            $product->increment('stock', $qty - $oldQty);
                        } elseif ($item->product_id !== $product->id) {
                            Product::where('id', $item->product_id)->decrement('stock', $oldQty);
                            $product->increment('stock', $qty);
                        }
                        $item->update([
                            'product_id' => $product->id,
                            'item_code' => $product->item_code,
                            'product_name' => $product->name . ($product->pack_size ? " ({$product->pack_size})" : ''),
                            'quantity' => $qty,
                            'cost_price' => $cost,
                            'line_total' => $lineTotal,
                        ]);
                        $keepIds[] = $item->id;
                        continue;
                    }
                }
                $newItem = PurchaseItem::create([
                    'purchase_id' => $purchase->id,
                    'product_id' => $product->id,
                    'item_code' => $product->item_code,
                    'product_name' => $product->name . ($product->pack_size ? " ({$product->pack_size})" : ''),
                    'quantity' => $qty,
                    'cost_price' => $cost,
                    'line_total' => $lineTotal,
                ]);
                $product->increment('stock', $qty);
                $keepIds[] = $newItem->id;
            }
            $deletedItems = $purchase->items()->whereNotIn('id', $keepIds)->get();
            foreach ($deletedItems as $item) {
                Product::where('id', $item->product_id)->decrement('stock', (int) $item->quantity);
            }
            $purchase->items()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('admin.purchases.index')->with('success', 'Purchase updated.');
    }

    public function destroy(Purchase $purchase)
    {
        $purchase->load('items');
        DB::transaction(function () use ($purchase) {
            foreach ($purchase->items as $item) {
                Product::where('id', $item->product_id)->decrement('stock', (int) $item->quantity);
            }
            $purchase->items()->delete();
            $purchase->delete();
        });
        return redirect()->route('admin.purchases.index')->with('success', 'Purchase deleted.');
    }
}
