<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuperAdminProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');

        $products = Product::query()
            ->with('category')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('item_code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%")
                        ->orWhere('pack_size', 'like', "%{$q}%");
                });
            })
            ->when($categoryId !== null && $categoryId !== '', function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(100)
            ->withQueryString();

        if ($request->wantsJson()) {
            $items = collect($products->items())->map(function (Product $p) {
                return [
                    'id' => $p->id,
                    'item_code' => $p->item_code,
                    'name' => $p->name,
                    'pack_size' => $p->pack_size,
                    'category_name' => $p->category ? $p->category->name : '—',
                    'price' => $p->formatted_price,
                    'cost_price' => $p->formatted_cost_price,
                    'stock' => $p->stock,
                    'image_url' => $p->image ? asset('storage/' . $p->image) : null,
                    'bv' => number_format($p->bv, 1),
                    'pv' => number_format($p->pv, 1),
                    'is_active' => $p->is_active,
                    'edit_url' => route('admin.products.edit', $p),
                    'destroy_url' => route('admin.products.destroy', $p),
                ];
            });
            return response()->json([
                'products' => $items,
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
            ]);
        }

        // Inventory reports (for Stock / Expiry / Low Stock section)
        $stockProducts = Product::with('category')->orderBy('name')->get();
        $expiryProducts = Product::whereNotNull('expiry_date')->orderBy('expiry_date')->get();
        $lowStockProducts = Product::whereRaw('min_stock > 0 AND stock <= min_stock')->orderBy('stock')->get();

        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        return view('admin.products.index', [
            'products' => $products,
            'q' => $q,
            'categoryId' => $categoryId,
            'categories' => $categories,
            'stockProducts' => $stockProducts,
            'expiryProducts' => $expiryProducts,
            'lowStockProducts' => $lowStockProducts,
        ]);
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.products.create', ['categories' => $categories]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_code' => ['required', 'string', 'max:20', Rule::unique('products', 'item_code')],
            'name' => ['required', 'string', 'max:255'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'bv' => ['required', 'numeric', 'min:0'],
            'pv' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        Product::create([
            'category_id' => $data['category_id'] ?? null,
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'pack_size' => $data['pack_size'] ?? null,
            'bv' => $data['bv'],
            'pv' => $data['pv'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'stock' => (int) ($data['stock'] ?? 0),
            'expiry_date' => $request->filled('expiry_date') ? $data['expiry_date'] : null,
            'batch_number' => $data['batch_number'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'image' => $imagePath,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        return view('admin.products.edit', ['product' => $product, 'categories' => $categories]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'item_code' => ['required', 'string', 'max:20', Rule::unique('products', 'item_code')->ignore($product->id)],
            'name' => ['required', 'string', 'max:255'],
            'pack_size' => ['nullable', 'string', 'max:100'],
            'bv' => ['required', 'numeric', 'min:0'],
            'pv' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'batch_number' => ['nullable', 'string', 'max:100'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $imagePath = $product->image;
        if ($request->hasFile('image')) {
            if ($product->image && Storage::disk('public')->exists($product->image)) {
                Storage::disk('public')->delete($product->image);
            }
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product->update([
            'category_id' => $data['category_id'] ?? null,
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'pack_size' => $data['pack_size'] ?? null,
            'bv' => $data['bv'],
            'pv' => $data['pv'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'stock' => (int) ($data['stock'] ?? 0),
            'expiry_date' => $request->filled('expiry_date') ? $data['expiry_date'] : null,
            'batch_number' => $data['batch_number'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'image' => $imagePath,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product deleted successfully.');
    }
}
