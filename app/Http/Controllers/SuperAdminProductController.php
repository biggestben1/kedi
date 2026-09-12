<?php

namespace App\Http\Controllers;

use App\Models\AnnexStock;
use App\Models\BranchStock;
use App\Models\Category;
use App\Models\HeadquartersStock;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SuperAdminProductController extends Controller
{
    /** Roles that keep inventory in a separate table (not products.stock). */
    protected function usesRoleInventory(?string $role): bool
    {
        return in_array($role, ['headquarters', 'branch', 'service_center', 'annex'], true);
    }

    protected function roleInventoryLabel(?string $role): ?string
    {
        return match ($role) {
            'headquarters' => 'HQ stock',
            'branch' => 'Branch stock',
            'service_center' => 'SC stock',
            'annex' => 'Annex stock',
            default => null,
        };
    }

    protected function getRoleInventoryStock(int $userId, string $role, int $productId): int
    {
        return match ($role) {
            'headquarters' => HeadquartersStock::getQuantity($userId, $productId),
            'branch' => BranchStock::getQuantity($userId, $productId),
            'service_center' => ServiceCenterStock::getQuantity($userId, $productId),
            'annex' => AnnexStock::getQuantity($userId, $productId),
            default => 0,
        };
    }

    protected function setRoleInventoryStock(int $userId, string $role, int $productId, int $qty): void
    {
        match ($role) {
            'headquarters' => HeadquartersStock::setQuantity($userId, $productId, $qty),
            'branch' => BranchStock::setQuantity($userId, $productId, $qty),
            'service_center' => ServiceCenterStock::setQuantity($userId, $productId, $qty),
            'annex' => AnnexStock::setQuantity($userId, $productId, $qty),
            default => null,
        };
    }

    protected function exportQuery(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $warehouseId = $request->query('warehouse_id');

        return Product::query()
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
            ->when($warehouseId !== null && $warehouseId !== '', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            })
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    protected function exportRows(Request $request): array
    {
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
        $showStockAsZero = ! $isSuperAdmin && ! $headquartersUserId && ! $branchUserId && ! $serviceCenterUserId && ! $annexUserId;

        $products = $this->exportQuery($request)->get();

        // Preload stock maps to avoid per-row queries (except `showStockAsZero`)
        $productIds = $products->pluck('id')->all();
        $hqStock = [];
        $branchStock = [];
        $scStock = [];
        $annexStock = [];
        if (! $showStockAsZero && $headquartersUserId && ! $isSuperAdmin) {
            $hqStock = HeadquartersStock::where('headquarters_user_id', $headquartersUserId)
                ->whereIn('product_id', $productIds)
                ->pluck('quantity', 'product_id')
                ->toArray();
        } elseif (! $showStockAsZero && $branchUserId) {
            $branchStock = BranchStock::where('branch_user_id', $branchUserId)
                ->whereIn('product_id', $productIds)
                ->pluck('quantity', 'product_id')
                ->toArray();
        } elseif (! $showStockAsZero && $serviceCenterUserId) {
            $scStock = ServiceCenterStock::where('service_center_user_id', $serviceCenterUserId)
                ->whereIn('product_id', $productIds)
                ->pluck('quantity', 'product_id')
                ->toArray();
        } elseif (! $showStockAsZero && $annexUserId) {
            $annexStock = AnnexStock::where('annex_user_id', $annexUserId)
                ->whereIn('product_id', $productIds)
                ->pluck('quantity', 'product_id')
                ->toArray();
        }

        return $products->map(function (Product $p) use ($isSuperAdmin, $headquartersUserId, $branchUserId, $serviceCenterUserId, $annexUserId, $showStockAsZero, $hqStock, $branchStock, $scStock, $annexStock) {
            if ($isSuperAdmin) {
                $stock = $p->stock;
            } else {
                $stock = $showStockAsZero ? 0 : $p->stock;
                if (! $showStockAsZero && $headquartersUserId) {
                    $stock = $hqStock[$p->id] ?? 0;
                } elseif (! $showStockAsZero && $branchUserId) {
                    $stock = $branchStock[$p->id] ?? 0;
                } elseif (! $showStockAsZero && $serviceCenterUserId) {
                    $stock = $scStock[$p->id] ?? 0;
                } elseif (! $showStockAsZero && $annexUserId) {
                    $stock = $annexStock[$p->id] ?? 0;
                }
            }

            return [
                'item_code' => (string) ($p->item_code ?? ''),
                'name' => (string) ($p->name ?? ''),
                'category' => (string) ($p->category?->name ?? ''),
                'pack_size' => (string) ($p->pack_size ?? ''),
                'cost_price' => (float) ($p->cost_price ?? 0),
                'selling_price' => (float) ($p->price ?? 0),
                'stock' => (int) $stock,
                'bv' => (float) ($p->bv ?? 0),
                'pv' => (float) ($p->pv ?? 0),
                'dpbv' => (bool) ($p->can_use_dpbv ?? true),
                'status' => (bool) ($p->is_active ?? false),
            ];
        })->all();
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $categoryId = $request->query('category_id');
        $warehouseId = $request->query('warehouse_id');

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
            ->when($warehouseId !== null && $warehouseId !== '', function ($query) use ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
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

        // Load headquarters stock for HQ, Service Center, and Annex (their parent HQ)
        // For Super Admin, keep product->stock as-is in reports
        if ($headquartersUserId && ! $isSuperAdmin) {
            $headquartersStockMap = HeadquartersStock::where('headquarters_user_id', $headquartersUserId)
                ->pluck('quantity', 'product_id')
                ->toArray();

            // (Inventory report data removed from UI)
        }

        $categories = Category::orderBy('sort_order')->orderBy('name')->get();

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
            'warehouseId' => $warehouseId,
            'categories' => $categories,
            'isHeadquarters' => $isHeadquarters,
            'headquartersUserId' => $headquartersUserId,
            'branchUserId' => $branchUserId,
            'serviceCenterUserId' => $serviceCenterUserId,
            'annexUserId' => $annexUserId,
            'showStockAsZero' => $showStockAsZero,
            'stockDebug' => $stockDebug,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $rows = $this->exportRows($request);

        $pdf = Pdf::loadView('admin.products.export-pdf', [
            'rows' => $rows,
            'generatedAt' => now(),
            'q' => (string) $request->query('q', ''),
            'categoryId' => (string) $request->query('category_id', ''),
        ]);

        return $pdf->download('products-'.now()->format('Y-m-d_His').'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $rows = $this->exportRows($request);

        $sheet = (new Spreadsheet())->getActiveSheet();
        $headers = [
            'Item Code',
            'Name',
            'Category',
            'Pack Size',
            'Cost Price',
            'Selling Price',
            'Stock',
            'BV',
            'PV',
            'DPBV',
            'Status',
        ];
        $sheet->fromArray($headers, null, 'A1', true);

        $data = array_map(function (array $r) {
            return [
                $r['item_code'],
                $r['name'],
                $r['category'],
                $r['pack_size'],
                $r['cost_price'],
                $r['selling_price'],
                $r['stock'],
                $r['bv'],
                $r['pv'],
                $r['dpbv'] ? 'Allowed' : 'Not Allowed',
                $r['status'] ? 'Active' : 'Inactive',
            ];
        }, $rows);
        if (! empty($data)) {
            $sheet->fromArray($data, null, 'A2', true);
        }

        // Basic sizing
        foreach (range('A', 'K') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($sheet->getParent());
        $tmp = tempnam(sys_get_temp_dir(), 'products_');
        $filePath = $tmp.'.xlsx';
        $writer->save($filePath);

        return response()->download($filePath, 'products-'.now()->format('Y-m-d_His').'.xlsx')->deleteFileAfterSend(true);
    }

    public function create()
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $user = auth()->user();
        $role = $user?->role?->name;
        $usesRoleInventory = $this->usesRoleInventory($role);

        return view('admin.products.create', [
            'categories' => $categories,
            'warehouses' => $warehouses,
            'usesRoleInventory' => $usesRoleInventory,
            'inventoryStockLabel' => $this->roleInventoryLabel($role),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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

        $user = $request->user();
        $role = $user?->role?->name ?? '';
        $usesRoleInventory = $this->usesRoleInventory($role);
        $stockInput = (int) ($data['stock'] ?? 0);
        $product = null;

        $productPayload = [
            'category_id' => $data['category_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'pack_size' => $data['pack_size'] ?? null,
            'bv' => $data['bv'],
            'pv' => $data['pv'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'stock' => $usesRoleInventory ? 0 : $stockInput,
            'expiry_date' => $request->filled('expiry_date') ? $data['expiry_date'] : null,
            'batch_number' => $data['batch_number'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'image' => $imagePath,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'can_use_dpbv' => $request->boolean('can_use_dpbv'),
        ];

        if ($usesRoleInventory) {
            DB::transaction(function () use ($productPayload, $user, $role, $stockInput, &$product) {
                $product = Product::create($productPayload);
                if ($stockInput > 0) {
                    $this->setRoleInventoryStock((int) $user->id, $role, (int) $product->id, $stockInput);
                }
            });
        } else {
            $product = Product::create($productPayload);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully.');
    }

    public function edit(Product $product)
    {
        $categories = Category::orderBy('sort_order')->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();
        $user = auth()->user();
        $role = $user?->role?->name;
        $usesRoleInventory = $this->usesRoleInventory($role);
        $roleStock = $usesRoleInventory
            ? $this->getRoleInventoryStock((int) $user->id, $role, (int) $product->id)
            : null;

        return view('admin.products.edit', [
            'product' => $product,
            'categories' => $categories,
            'warehouses' => $warehouses,
            'usesRoleInventory' => $usesRoleInventory,
            'inventoryStockLabel' => $this->roleInventoryLabel($role),
            'roleStock' => $roleStock,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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

        $user = $request->user();
        $role = $user?->role?->name ?? '';
        $usesRoleInventory = $this->usesRoleInventory($role);
        $stockInput = (int) ($data['stock'] ?? 0);

        $payload = [
            'category_id' => $data['category_id'] ?? null,
            'warehouse_id' => $data['warehouse_id'] ?? null,
            'item_code' => $data['item_code'],
            'name' => $data['name'],
            'pack_size' => $data['pack_size'] ?? null,
            'bv' => $data['bv'],
            'pv' => $data['pv'],
            'price' => $data['price'],
            'cost_price' => $data['cost_price'] ?? null,
            'expiry_date' => $request->filled('expiry_date') ? $data['expiry_date'] : null,
            'batch_number' => $data['batch_number'] ?? null,
            'min_stock' => (int) ($data['min_stock'] ?? 0),
            'image' => $imagePath,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => $request->boolean('is_active'),
            'can_use_dpbv' => $request->boolean('can_use_dpbv'),
        ];

        if ($usesRoleInventory) {
            DB::transaction(function () use ($user, $role, $product, $payload, $stockInput) {
                $product->update($payload);
                $this->setRoleInventoryStock((int) $user->id, $role, (int) $product->id, $stockInput);
            });
        } else {
            $payload['stock'] = $stockInput;
            $product->update($payload);
        }

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
