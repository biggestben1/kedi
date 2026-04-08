<?php

namespace App\Http\Controllers;

use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\HeadquartersStock;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SuperAdminProductController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');

        $currentUser = $request->user();
        $isSuperAdmin = $currentUser?->isSuperAdmin() ?? false;
        $isHeadquarters = $currentUser?->role?->name === 'headquarters';
        $headquartersUserId = $isHeadquarters ? $currentUser->id : null;
        $isServiceCenter = $currentUser?->role?->name === 'service_center';
        $isAnnex = $currentUser?->role?->name === 'annex';
        $isBranch = $currentUser?->role?->name === 'branch';
        $branchUserId = $isBranch ? (int) $currentUser->id : null;
        $serviceCenterUserId = $isServiceCenter ? (int) $currentUser->id : null;
        $annexUserId = $isAnnex ? (int) $currentUser->id : null;

        // For Super Admin, always show product stock as stored on the product
        $showStockAsZero = ! $isSuperAdmin && ! $headquartersUserId && ! $branchUserId && ! $serviceCenterUserId && ! $annexUserId;

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
            $items = collect($products->items())->map(function (Product $p) use ($isSuperAdmin, $headquartersUserId, $branchUserId, $serviceCenterUserId, $annexUserId, $showStockAsZero) {
                // Super Admin: always see main product stock
                if ($isSuperAdmin) {
                    $stock = $p->stock;
                } else {
                    $stock = $showStockAsZero ? 0 : $p->stock;
                    if (! $showStockAsZero && $headquartersUserId) {
                        $stock = HeadquartersStock::getQuantity($headquartersUserId, $p->id);
                    } elseif (! $showStockAsZero && $branchUserId) {
                        $stock = BranchStock::getQuantity($branchUserId, $p->id);
                    } elseif (! $showStockAsZero && $serviceCenterUserId) {
                        $stock = ServiceCenterStock::getQuantity($serviceCenterUserId, $p->id);
                    } elseif (! $showStockAsZero && $annexUserId) {
                        $stock = AnnexStock::getQuantity($annexUserId, $p->id);
                    }
                }

                return [
                    'id' => $p->id,
                    'item_code' => $p->item_code,
                    'name' => $p->name,
                    'pack_size' => $p->pack_size,
                    'category_name' => $p->category ? $p->category->name : '—',
                    'price' => $p->formatted_price,
                    'cost_price' => $p->formatted_cost_price,
                    'stock' => $stock,
                    'image_url' => $p->image_url,
                    'bv' => number_format($p->bv, 1),
                    'pv' => number_format($p->pv, 1),
                    'can_use_dpbv' => $p->can_use_dpbv ?? true,
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

        // Load headquarters stock for HQ, Service Center, and Annex (their parent HQ)
        // For Super Admin, keep product->stock as-is in reports
        if ($headquartersUserId && ! $isSuperAdmin) {
            $headquartersStockMap = HeadquartersStock::where('headquarters_user_id', $headquartersUserId)
                ->pluck('quantity', 'product_id')
                ->toArray();

            $stockProducts = $stockProducts->map(function ($product) use ($headquartersStockMap) {
                $product->stock = $headquartersStockMap[$product->id] ?? 0;

                return $product;
            });
        }

        // Branch (with no HQ): show stock as 0 in reports
        if ($showStockAsZero) {
            $stockProducts = $stockProducts->map(function ($product) {
                $product->stock = 0;

                return $product;
            });
            $lowStockProducts = $lowStockProducts->map(function ($product) {
                $product->stock = 0;

                return $product;
            });
        }

        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

        if ($branchUserId) {
            $branchStockMap = BranchStock::where('branch_user_id', $branchUserId)
                ->pluck('quantity', 'product_id')
                ->toArray();

            $stockProducts = $stockProducts->map(function ($product) use ($branchStockMap) {
                $product->stock = $branchStockMap[$product->id] ?? 0;

                return $product;
            });
        }

        if ($serviceCenterUserId) {
            $scStockMap = ServiceCenterStock::where('service_center_user_id', $serviceCenterUserId)
                ->pluck('quantity', 'product_id')
                ->toArray();

            $stockProducts = $stockProducts->map(function ($product) use ($scStockMap) {
                $product->stock = $scStockMap[$product->id] ?? 0;

                return $product;
            });
        }

        if ($annexUserId) {
            $annexStockMap = AnnexStock::where('annex_user_id', $annexUserId)
                ->pluck('quantity', 'product_id')
                ->toArray();

            $stockProducts = $stockProducts->map(function ($product) use ($annexStockMap) {
                $product->stock = $annexStockMap[$product->id] ?? 0;

                return $product;
            });
        }

        $stockDebug = null;
        if ($request->query('debug') === '1') {
            $firstProduct = $products->first();
            $sampleStock = 0;
            $firstProductName = null;
            if ($firstProduct) {
                $firstProductName = $firstProduct->display_name ?? $firstProduct->name;
                if ($branchUserId) {
                    $sampleStock = BranchStock::getQuantity($branchUserId, $firstProduct->id);
                } elseif ($serviceCenterUserId) {
                    $sampleStock = ServiceCenterStock::getQuantity($serviceCenterUserId, $firstProduct->id);
                } elseif ($annexUserId) {
                    $sampleStock = AnnexStock::getQuantity($annexUserId, $firstProduct->id);
                } else {
                    $sampleStock = $headquartersUserId ? HeadquartersStock::getQuantity($headquartersUserId, $firstProduct->id) : 0;
                }
            }
            $sampleWithStock = null;
            if ($branchUserId) {
                $row = BranchStock::where('branch_user_id', $branchUserId)->where('quantity', '>', 0)->first();
                if ($row && $row->product) {
                    $sampleWithStock = $row->product->display_name.': '.$row->quantity;
                }
            } elseif ($serviceCenterUserId) {
                $row = ServiceCenterStock::where('service_center_user_id', $serviceCenterUserId)->where('quantity', '>', 0)->first();
                if ($row && $row->product) {
                    $sampleWithStock = $row->product->display_name.': '.$row->quantity;
                }
            } elseif ($annexUserId) {
                $row = AnnexStock::where('annex_user_id', $annexUserId)->where('quantity', '>', 0)->first();
                if ($row && $row->product) {
                    $sampleWithStock = $row->product->display_name.': '.$row->quantity;
                }
            }
            $stockDebug = [
                'user_id' => $currentUser->id,
                'user_name' => $currentUser->name,
                'role' => $currentUser->role?->name,
                'branch_user_id' => $branchUserId,
                'service_center_user_id' => $serviceCenterUserId,
                'annex_user_id' => $annexUserId,
                'headquarters_user_id' => $headquartersUserId,
                'show_stock_as_zero' => $showStockAsZero,
                'first_product' => $firstProductName,
                'first_product_stock' => $sampleStock,
                'sample_with_stock' => $sampleWithStock,
                'branch_stock_rows' => $branchUserId ? BranchStock::where('branch_user_id', $branchUserId)->count() : 0,
            ];
        }

        return view('admin.products.index', [
            'products' => $products,
            'q' => $q,
            'categoryId' => $categoryId,
            'categories' => $categories,
            'stockProducts' => $stockProducts,
            'expiryProducts' => $expiryProducts,
            'lowStockProducts' => $lowStockProducts,
            'isHeadquarters' => $isHeadquarters,
            'headquartersUserId' => $headquartersUserId,
            'branchUserId' => $branchUserId,
            'serviceCenterUserId' => $serviceCenterUserId,
            'annexUserId' => $annexUserId,
            'showStockAsZero' => $showStockAsZero,
            'stockDebug' => $stockDebug,
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
            'can_use_dpbv' => $request->boolean('can_use_dpbv'),
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
            'can_use_dpbv' => ['boolean'],
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
            'can_use_dpbv' => $request->boolean('can_use_dpbv'),
        ]);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product moved to trash.');
    }

    public function trashed()
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $products = Product::onlyTrashed()->with('category')->paginate(50);
        return view('admin.products.trashed', compact('products'));
    }

    public function restore($id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('admin.products.trashed')->with('success', "Product {$product->name} has been restored.");
    }

    public function forceDelete($id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $product = Product::withTrashed()->findOrFail($id);

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->forceDelete();

        return redirect()->route('admin.products.trashed')->with('success', "Product {$product->name} has been permanently deleted.");
    }
}
