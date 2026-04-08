<?php

namespace App\Http\Controllers;

use App\Models\AnnexStock;
use App\Models\BackOrder;
use App\Models\BranchStock;
use App\Models\HeadquartersStock;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ServiceCenterStock;
use App\Models\User;
use App\Models\WalletTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuperAdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $annexOnly = $request->user()?->role?->name === 'annex';

        $customerIds = $resellerOnly ? $request->user()->createdUsers()->pluck('id')->all() : null;
        $branchCustomerIds = $branchOnly ? $request->user()->createdUsers()->pluck('id')->all() : null;

        // Service Center: own invoices + invoices from users they created (Annex, Dispatch, Accountant)
        $serviceCenterUserIds = null;
        if ($serviceCenterOnly) {
            $scId = $request->user()->id;
            $serviceCenterUserIds = array_merge(
                [$scId],
                $request->user()
                    ->createdUsers()
                    ->whereHas('role', function ($q) {
                        $q->whereIn('name', ['annex', 'dispatch', 'accountant']);
                    })
                    ->pluck('id')
                    ->all()
            );
        }

        // Annex: only invoices where they are the customer (user_id = self)
        $annexOwnInvoiceIds = $annexOnly ? [$request->user()->id] : null;

        // Headquarters: get IDs of Service Center, Annex, and Branch users they created
        $headquartersUserIds = null;
        if ($headquartersOnly) {
            $headquartersUserIds = $request->user()
                ->createdUsers()
                ->whereHas('role', function ($q) {
                    $q->whereIn('name', ['service_center', 'annex', 'branch']);
                })
                ->pluck('id')
                ->all();
        }

        $invoices = Invoice::with('user', 'order')
            ->when($customerIds !== null, function ($q) use ($customerIds) {
                $q->whereIn('user_id', $customerIds);
            })
            ->when($branchCustomerIds !== null, function ($q) use ($branchCustomerIds) {
                $q->whereIn('user_id', $branchCustomerIds);
            })
            ->when($serviceCenterUserIds !== null, function ($q) use ($serviceCenterUserIds) {
                $q->whereIn('user_id', $serviceCenterUserIds);
            })
            ->when($headquartersUserIds !== null, function ($q) use ($headquartersUserIds) {
                $q->whereIn('user_id', $headquartersUserIds);
            })
            ->when($annexOwnInvoiceIds !== null, function ($q) use ($annexOwnInvoiceIds) {
                $q->whereIn('user_id', $annexOwnInvoiceIds);
            })
            ->when($request->query('status'), function ($q, $status) {
                $q->where('status', $status);
            })
            ->when($request->query('q'), function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('customer_name', 'like', "%{$search}%")
                        ->orWhere('customer_email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        // Invoices that the current user must approve (hide "Move to dispatch" so they use Approve and stock is deducted from HQ/branch)
        $invoiceIdsRequireApproval = [];
        $currentUser = $request->user();
        if ($currentUser) {
            foreach ($invoices as $inv) {
                if ($inv->is_approved || $inv->order) {
                    continue;
                }
                $invoiceUser = $inv->user;
                if (! $invoiceUser) {
                    continue;
                }
                if ($invoiceUser->relationLoaded('role') === false) {
                    $invoiceUser->load('role');
                }
                $invoiceUserRole = $invoiceUser->role?->name;
                $invoiceUserCreatedBy = (int) ($invoiceUser->created_by_user_id ?? 0);
                $approverRole = $currentUser->role?->name ?? '';
                $canApprove = false;
                if ($currentUser->isSuperAdmin() && $invoiceUserRole === 'headquarters') {
                    $canApprove = true;
                } elseif ($approverRole === 'headquarters' && ((int) $inv->user_id === (int) $currentUser->id || ($invoiceUserCreatedBy === (int) $currentUser->id && in_array($invoiceUserRole, ['branch', 'annex', 'service_center'], true)))) {
                    $canApprove = true;
                } elseif ($approverRole === 'branch' && $invoiceUserCreatedBy === (int) $currentUser->id && in_array($invoiceUserRole, ['annex', 'service_center'], true)) {
                    $canApprove = true;
                } elseif ($approverRole === 'service_center' && $invoiceUserCreatedBy === (int) $currentUser->id && $invoiceUserRole === 'annex') {
                    $canApprove = true;
                }
                if ($canApprove) {
                    $invoiceIdsRequireApproval[] = $inv->id;
                }
            }
        }

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'statusFilter' => $request->query('status'),
            'search' => $request->query('q'),
            'invoiceIdsRequireApproval' => $invoiceIdsRequireApproval,
        ]);
    }

    public function create(Request $request)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        $users = $resellerOnly
            ? $request->user()->createdUsers()->with('role')->orderBy('name')->get()
            : ($branchOnly || $serviceCenterOnly
                ? $request->user()->createdUsers()->with('role')->orderBy('name')->get()
                : User::with('role')->orderBy('name')->get());
        $selectedUser = null;
        $products = collect();
        $branchStockByProduct = [];

        if ($branchOnly) {
            $branchStock = BranchStock::where('branch_user_id', $request->user()->id)
                ->where('quantity', '>', 0)
                ->with('product')
                ->get();
            foreach ($branchStock as $bs) {
                if ($bs->product && $bs->product->is_active) {
                    $products->push($bs->product);
                    $branchStockByProduct[$bs->product_id] = $bs->quantity;
                }
            }
            $products = $products->sortBy('name')->values();
        } elseif ($serviceCenterOnly) {
            $scStock = ServiceCenterStock::where('service_center_user_id', $request->user()->id)
                ->where('quantity', '>', 0)
                ->with('product')
                ->get();
            foreach ($scStock as $bs) {
                if ($bs->product && $bs->product->is_active) {
                    $products->push($bs->product);
                    $branchStockByProduct[$bs->product_id] = $bs->quantity;
                }
            }
            $products = $products->sortBy('name')->values();
        } else {
            $products = Product::where('is_active', true)->orderBy('name')->get();
        }

        if ($request->filled('user_id')) {
            $selectedUser = User::find($request->query('user_id'));
            $allowed = ! $resellerOnly && ! $branchOnly && ! $serviceCenterOnly
                || ($resellerOnly && $selectedUser && $selectedUser->created_by_user_id === $request->user()->id)
                || ($branchOnly && $selectedUser && $selectedUser->created_by_user_id === $request->user()->id)
                || ($serviceCenterOnly && $selectedUser && $selectedUser->created_by_user_id === $request->user()->id);
            if (! $allowed) {
                $selectedUser = null;
            }
        }

        return view('admin.invoices.create', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'products' => $products,
            'branchStockByProduct' => $branchStockByProduct,
            'branchOnly' => $branchOnly,
            'serviceCenterOnly' => $serviceCenterOnly,
        ]);
    }

    public function store(Request $request)
    {
        // Check if product_quantities are provided (always use products now)
        $hasProductQuantities = $request->has('product_quantities') && is_array($request->input('product_quantities'));
        $productQuantities = $hasProductQuantities ? array_filter($request->input('product_quantities', []), fn ($q) => (float) $q > 0) : [];

        if ($hasProductQuantities && ! empty($productQuantities)) {
            $data = $request->validate([
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'customer_name' => ['nullable', 'string', 'max:255'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:50'],
                'customer_address' => ['nullable', 'string'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
                'notes' => ['nullable', 'string'],
                'product_quantities' => ['required', 'array'],
                'product_quantities.*' => ['numeric', 'min:0'],
            ]);

            $user = ! empty($data['user_id']) ? User::findOrFail($data['user_id']) : null;
            $productIds = array_keys($productQuantities);
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $items = [];
            foreach ($productQuantities as $productId => $qty) {
                $product = $products->get($productId);
                if (! $product) {
                    continue;
                }
                $qty = (float) $qty;
                // Use user-specific price if user is selected, otherwise use regular price
                $unitPrice = $user ? $product->getPriceForUser($user) : $product->price;
                $items[] = [
                    'item_name' => $product->display_name,
                    'description' => null,
                    'quantity' => $qty,
                    'unit' => $product->pack_size ?? 'pcs',
                    'unit_price' => $unitPrice,
                    'line_total' => $qty * $unitPrice,
                ];
            }
            if (empty($items)) {
                return redirect()->back()->withInput()->withErrors(['product_quantities' => 'Enter quantity for at least one product.']);
            }
        } else {
            $data = $request->validate([
                'user_id' => ['nullable', 'integer', 'exists:users,id'],
                'customer_name' => ['nullable', 'string', 'max:255'],
                'customer_email' => ['nullable', 'email', 'max:255'],
                'customer_phone' => ['nullable', 'string', 'max:50'],
                'customer_address' => ['nullable', 'string'],
                'invoice_date' => ['required', 'date'],
                'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
                'tax' => ['nullable', 'numeric', 'min:0'],
                'discount' => ['nullable', 'numeric', 'min:0'],
                'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
                'notes' => ['nullable', 'string'],
                'items' => ['required', 'array', 'min:1'],
                'items.*.item_name' => ['required', 'string', 'max:255'],
                'items.*.description' => ['nullable', 'string'],
                'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
                'items.*.unit' => ['nullable', 'string', 'max:50'],
                'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            ]);
            $items = [];
            foreach ($data['items'] as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $items[] = [
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $qty,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $price,
                    'line_total' => $qty * $price,
                ];
            }
        }

        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';
        if ($resellerOnly && ! empty($data['user_id']) && ! in_array((int) $data['user_id'], $request->user()->createdUsers()->pluck('id')->all(), true)) {
            return redirect()->back()->withInput()->withErrors(['user_id' => 'You can only create invoices for your customers.']);
        }
        if ($branchOnly && ! empty($data['user_id']) && ! in_array((int) $data['user_id'], $request->user()->createdUsers()->pluck('id')->all(), true)) {
            return redirect()->back()->withInput()->withErrors(['user_id' => 'You can only create invoices for your Annex or Service Center users.']);
        }
        if ($serviceCenterOnly && ! empty($data['user_id']) && ! in_array((int) $data['user_id'], $request->user()->createdUsers()->pluck('id')->all(), true)) {
            return redirect()->back()->withInput()->withErrors(['user_id' => 'You can only create invoices for your Annex users.']);
        }

        // Check branch stock if branch user and using products
        if ($branchOnly && $hasProductQuantities && ! empty($productQuantities)) {
            foreach ($productQuantities as $productId => $qty) {
                $avail = BranchStock::getQuantity($request->user()->id, (int) $productId);
                if ($avail < (float) $qty) {
                    $name = Product::find($productId)?->name ?? "Product #{$productId}";

                    return redirect()->back()->withInput()->withErrors(['product_quantities' => "Insufficient branch stock for {$name}. Available: {$avail}."]);
                }
            }
        }
        // Check service center stock if service center and using products (no deduct at create; stock moves on approve)
        if ($serviceCenterOnly && $hasProductQuantities && ! empty($productQuantities)) {
            foreach ($productQuantities as $productId => $qty) {
                $avail = ServiceCenterStock::getQuantity($request->user()->id, (int) $productId);
                if ($avail < (float) $qty) {
                    $name = Product::find($productId)?->name ?? "Product #{$productId}";

                    return redirect()->back()->withInput()->withErrors(['product_quantities' => "Insufficient service center stock for {$name}. Available: {$avail}."]);
                }
            }
        }

        $data['invoice_number'] = $this->generateInvoiceNumber();

        $subtotal = array_sum(array_column($items, 'line_total'));
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = $subtotal + $tax - $discount;

        $invoice = null;
        $branchUserId = $branchOnly ? $request->user()->id : null;
        if (! $branchUserId && ! empty($data['user_id'])) {
            $customer = User::with('role')->find($data['user_id']);
            if ($customer && $customer->role?->name === 'branch') {
                $branchUserId = (int) $customer->id;
            } elseif ($customer && in_array($customer->role?->name ?? '', ['annex', 'service_center'], true) && $customer->created_by_user_id) {
                $branchUserId = (int) $customer->created_by_user_id;
            }
        }
        $deductOnCreate = $branchOnly; // Only Branch deducts at create; SC stock moves on approve
        DB::transaction(function () use ($data, $items, $subtotal, $tax, $discount, $total, $branchUserId, $productQuantities, $hasProductQuantities, $deductOnCreate, &$invoice) {
            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'user_id' => $data['user_id'] ?? null,
                'branch_user_id' => $branchUserId,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $index => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                    'sort_order' => $index,
                ]);
            }

            if ($deductOnCreate && $branchUserId && $hasProductQuantities && ! empty($productQuantities)) {
                foreach ($productQuantities as $productId => $qty) {
                    if ((float) $qty > 0) {
                        BranchStock::decrementStock($branchUserId, (int) $productId, (float) $qty);
                    }
                }
            }
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Invoice created successfully.')
            ->with('created_invoice_id', $invoice->id);
    }

    public function show(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        $headquartersOnly = $request->user()?->role?->name === 'headquarters';
        $serviceCenterOnly = $request->user()?->role?->name === 'service_center';

        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only view invoices for your customers.');
            }
        }
        if ($branchOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only view invoices for your users.');
            }
        }
        if ($headquartersOnly) {
            $allowedIds = $request->user()->createdUsers()
                ->whereHas('role', fn ($q) => $q->whereIn('name', ['branch', 'service_center', 'annex']))
                ->pluck('id')
                ->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $allowedIds, true)) {
                $ownId = (int) $request->user()->id;
                if ((int) $invoice->user_id !== $ownId) {
                    abort(403, 'Access denied. You can only view invoices for your organization.');
                }
            }
        }
        if ($serviceCenterOnly) {
            $customerIds = array_merge(
                [$request->user()->id],
                $request->user()->createdUsers()
                    ->whereHas('role', fn ($q) => $q->where('name', 'annex'))
                    ->pluck('id')
                    ->all()
            );
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only view invoices for your Annex or your own.');
            }
        }
        if ($request->user()?->role?->name === 'annex') {
            if (! $invoice->user_id || (int) $invoice->user_id !== (int) $request->user()->id) {
                abort(403, 'Access denied. You can only view your own invoices.');
            }
        }

        $invoice->load('items', 'user.role', 'order');
        $products = Product::where('is_active', true)->get();
        $productsByName = $products->keyBy(fn ($p) => $p->display_name);
        $outOfStockItemIds = [];
        $inStockItemIds = [];
        $itemStock = []; // item_id => quantity (what the viewer has in stock)
        $currentUser = $request->user();
        $viewerIsBranch = $currentUser && $currentUser->role?->name === 'branch';
        $viewerIsHeadquarters = $currentUser && $currentUser->role?->name === 'headquarters';
        $viewerIsServiceCenter = $currentUser && $currentUser->role?->name === 'service_center';

        $viewerIsSuperAdmin = $currentUser && $currentUser->isSuperAdmin();

        foreach ($invoice->items as $item) {
            $product = $this->findProductForItem($products, $item->item_name);
            $qty = 0;
            if ($product) {
                if ($viewerIsSuperAdmin) {
                    $qty = (int) $product->stock;
                } elseif ($viewerIsServiceCenter && $invoice->user_id && $invoice->user && (int) $invoice->user->created_by_user_id === (int) $currentUser->id && $invoice->user->role?->name === 'annex') {
                    $qty = ServiceCenterStock::getQuantity($currentUser->id, (int) $product->id);
                } elseif ($viewerIsBranch) {
                    // Branch approving SC/Annex: stock comes from Branch. Branch approving own: also Branch stock.
                    $qty = BranchStock::getQuantity($currentUser->id, (int) $product->id);
                } elseif ($viewerIsHeadquarters) {
                    $qty = HeadquartersStock::getQuantity($currentUser->id, (int) $product->id);
                } elseif ($invoice->branch_user_id) {
                    $qty = BranchStock::getQuantity((int) $invoice->branch_user_id, (int) $product->id);
                } elseif ($invoice->user_id && $invoice->user && $invoice->user->role?->name === 'headquarters') {
                    $qty = HeadquartersStock::getQuantity((int) $invoice->user_id, (int) $product->id);
                } elseif ($invoice->user_id && $invoice->user && $invoice->user->role?->name === 'branch') {
                    $qty = BranchStock::getQuantity((int) $invoice->user_id, (int) $product->id);
                } elseif ($invoice->user_id && $invoice->user && in_array($invoice->user->role?->name ?? '', ['annex', 'service_center'], true)) {
                    $hqUserId = $this->getFulfillingHqIdForInvoiceCustomer($invoice->user);
                    $qty = $hqUserId > 0 ? HeadquartersStock::getQuantity($hqUserId, (int) $product->id) : (int) $product->stock;
                } else {
                    $qty = (int) $product->stock;
                }
                $itemStock[$item->id] = $qty;
                if ($qty <= 0) {
                    $outOfStockItemIds[] = $item->id;
                } else {
                    $inStockItemIds[] = $item->id;
                }
            }
        }

        // Back order breakdown: giving_now = min(ordered, stock), back_order_qty = max(0, ordered - stock)
        $backOrderLines = [];
        $itemGivingNow = [];
        $itemBackOrderQty = [];
        foreach ($invoice->items as $item) {
            $ordered = (float) $item->quantity;
            $inStock = (int) ($itemStock[$item->id] ?? 0);
            $givingNow = min($ordered, $inStock);
            $backOrderQty = max(0, $ordered - $inStock);
            $itemGivingNow[$item->id] = $givingNow;
            $itemBackOrderQty[$item->id] = $backOrderQty;
            if ($backOrderQty > 0) {
                $backOrderLines[] = (object) [
                    'item' => $item,
                    'item_name' => $item->item_name,
                    'ordered' => $ordered,
                    'in_stock' => $inStock,
                    'giving_now' => $givingNow,
                    'back_order_qty' => $backOrderQty,
                ];
            }
        }

        // Approval chain: Super Admin approves HQ; HQ approves Branch/Annex/Service Center; Branch approves SC/Annex; Service Center approves Annex
        $canApprove = false;
        $isHeadquartersInvoice = false;
        $headquartersUserId = null;
        $currentUser = $request->user();

        if (! $invoice->is_approved && $invoice->user_id) {
            if ($invoice->relationLoaded('user') === false || ($invoice->user && ! $invoice->user->relationLoaded('role'))) {
                $invoice->load('user.role');
            }
            $invoiceUserRole = $invoice->user?->role?->name;
            $invoiceUserCreatedBy = (int) ($invoice->user?->created_by_user_id ?? 0);

            if ($currentUser && $currentUser->isSuperAdmin()) {
                if ($invoiceUserRole === 'headquarters') {
                    $isHeadquartersInvoice = true;
                    $headquartersUserId = $invoice->user_id;
                    $canApprove = true;
                } elseif (! $invoice->user_id) {
                    $headquartersUsers = User::whereHas('role', fn ($q) => $q->where('name', 'headquarters'))->get();
                    if ($headquartersUsers->count() >= 1) {
                        $isHeadquartersInvoice = true;
                        $headquartersUserId = $headquartersUsers->first()->id;
                        $canApprove = true;
                    }
                }
            } elseif ($currentUser && $currentUser->role?->name === 'headquarters') {
                if ((int) $invoice->user_id === (int) $currentUser->id) {
                    $isHeadquartersInvoice = true;
                    $headquartersUserId = $currentUser->id;
                    $canApprove = true;
                } elseif ($invoiceUserCreatedBy === (int) $currentUser->id && in_array($invoiceUserRole, ['branch', 'annex', 'service_center'], true)) {
                    $canApprove = true;
                }
            } elseif ($currentUser && $currentUser->role?->name === 'branch') {
                if ($invoiceUserCreatedBy === (int) $currentUser->id && in_array($invoiceUserRole, ['annex', 'service_center'], true)) {
                    $canApprove = true;
                }
            } elseif ($currentUser && $currentUser->role?->name === 'service_center') {
                if ($invoiceUserCreatedBy === (int) $currentUser->id && $invoiceUserRole === 'annex') {
                    $canApprove = true;
                }
            }
        }

        $orderExists = Order::where('invoice_id', $invoice->id)->exists();
        if ($orderExists) {
            $canApprove = false;
        }
        $canMoveToDispatch = (bool) $invoice->user_id && ! $orderExists;
        $existingOrder = $orderExists ? Order::where('invoice_id', $invoice->id)->first() : null;

        return view('admin.invoices.show', [
            'invoice' => $invoice,
            'outOfStockItemIds' => $outOfStockItemIds,
            'inStockItemIds' => $inStockItemIds,
            'itemStock' => $itemStock,
            'itemGivingNow' => $itemGivingNow,
            'itemBackOrderQty' => $itemBackOrderQty,
            'backOrderLines' => $backOrderLines,
            'canApprove' => $canApprove,
            'canMoveToDispatch' => $canMoveToDispatch,
            'existingOrder' => $existingOrder,
            'isHeadquartersInvoice' => $isHeadquartersInvoice,
            'headquartersUserId' => $headquartersUserId,
        ]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your customers.');
            }
        }
        if ($branchOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your Annex users.');
            }
        }
        $invoice->load('items', 'user');
        $users = $resellerOnly
            ? $request->user()->createdUsers()->with('role')->orderBy('name')->get()
            : User::with('role')->orderBy('name')->get();

        $products = Product::where('is_active', true)->get();
        $productsByName = $products->keyBy(fn ($p) => $p->display_name);
        $outOfStockItemIds = [];
        $inStockItemIds = [];
        foreach ($invoice->items as $item) {
            $product = $this->findProductForItem($products, $item->item_name);
            if ($product) {
                if ((int) $product->stock <= 0) {
                    $outOfStockItemIds[] = $item->id;
                } else {
                    $inStockItemIds[] = $item->id;
                }
            }
        }

        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'users' => $users,
            'outOfStockItemIds' => $outOfStockItemIds,
            'inStockItemIds' => $inStockItemIds,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your customers.');
            }
        }
        if ($branchOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your Annex users.');
            }
        }
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:100', Rule::unique('invoices', 'invoice_number')->ignore($invoice->id)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'string', Rule::in(['draft', 'sent', 'paid', 'overdue', 'cancelled'])],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:invoice_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $invoice) {
            $subtotal = 0;
            foreach ($data['items'] as $item) {
                $subtotal += (float) $item['quantity'] * (float) $item['unit_price'];
            }
            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $total = $subtotal + $tax - $discount;

            $invoice->update([
                'invoice_number' => $data['invoice_number'],
                'user_id' => $data['user_id'] ?? null,
                'customer_name' => $data['customer_name'] ?? null,
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'customer_address' => $data['customer_address'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => $data['status'],
                'notes' => $data['notes'] ?? null,
            ]);

            $keepIds = [];
            foreach ($data['items'] as $index => $item) {
                $lineTotal = (float) $item['quantity'] * (float) $item['unit_price'];
                if (! empty($item['id'])) {
                    $invoiceItem = InvoiceItem::where('invoice_id', $invoice->id)->find($item['id']);
                    if ($invoiceItem) {
                        $invoiceItem->update([
                            'item_name' => $item['item_name'],
                            'description' => $item['description'] ?? null,
                            'quantity' => (float) $item['quantity'],
                            'unit' => $item['unit'] ?? null,
                            'unit_price' => (float) $item['unit_price'],
                            'line_total' => $lineTotal,
                            'sort_order' => $index,
                        ]);
                        $keepIds[] = $invoiceItem->id;

                        continue;
                    }
                }
                $newItem = InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'item_name' => $item['item_name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => (float) $item['quantity'],
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => (float) $item['unit_price'],
                    'line_total' => $lineTotal,
                    'sort_order' => $index,
                ]);
                $keepIds[] = $newItem->id;
            }
            $invoice->items()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        if ($resellerOnly && $invoice->user_id && ! in_array((int) $invoice->user_id, $request->user()->createdUsers()->pluck('id')->all(), true)) {
            abort(403, 'Access denied. You can only delete invoices for your customers.');
        }
        if ($branchOnly && $invoice->user_id && ! in_array((int) $invoice->user_id, $request->user()->createdUsers()->pluck('id')->all(), true)) {
            abort(403, 'Access denied. You can only delete invoices for your Annex users.');
        }
        $invoice->items()->delete();
        $invoice->delete();

        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
    }

    public function moveToDispatch(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $branchOnly = $request->user()?->role?->name === 'branch';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied.');
            }
        }
        if ($branchOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied.');
            }
        }

        if (! $invoice->user_id) {
            return redirect()->back()->with('error', 'Invoice must have a customer (user) to move to dispatch.');
        }

        if (Order::where('invoice_id', $invoice->id)->exists()) {
            return redirect()->back()->with('error', 'This invoice has already been moved to dispatch.');
        }

        // Invoices that require approval (e.g. branch invoice for HQ) must use Approve so stock is deducted from HQ/branch
        $invoice->load('user.role');
        $invUser = $invoice->user;
        $user = $request->user();
        $canApprove = false;
        if ($invUser && $user) {
            $invRole = $invUser->role?->name;
            $invCreatedBy = (int) ($invUser->created_by_user_id ?? 0);
            $approverRole = $user->role?->name ?? '';
            if ($user->isSuperAdmin() && $invRole === 'headquarters') {
                $canApprove = true;
            } elseif ($approverRole === 'headquarters' && ((int) $invoice->user_id === (int) $user->id || ($invCreatedBy === (int) $user->id && in_array($invRole, ['branch', 'annex', 'service_center'], true)))) {
                $canApprove = true;
            } elseif ($approverRole === 'branch' && $invCreatedBy === (int) $user->id && in_array($invRole, ['annex', 'service_center'], true)) {
                $canApprove = true;
            } elseif ($approverRole === 'service_center' && $invCreatedBy === (int) $user->id && $invRole === 'annex') {
                $canApprove = true;
            }
        }
        if ($canApprove) {
            return redirect()->route('admin.invoices.show', $invoice)->with('error', 'Use the Approve button on this invoice so stock is deducted from your inventory. Do not use Move to dispatch for this invoice.');
        }

        $invoice->load('items', 'user');

        if ($invoice->items->isEmpty()) {
            return redirect()->back()->with('error', 'Invoice has no items.');
        }

        $products = Product::where('is_active', true)->get();
        $productsByName = $products->keyBy(fn ($p) => $p->display_name);

        // Determine stock per item: branch invoice → branch stock, else main product stock
        $itemQuantities = [];
        $backOrdersToCreate = [];
        foreach ($invoice->items as $invItem) {
            $product = $this->findProductForItem($products, $invItem->item_name);
            $ordered = (float) $invItem->quantity;
            $available = 0;
            if ($product) {
                if ($invoice->branch_user_id) {
                    $available = BranchStock::getQuantity($invoice->branch_user_id, (int) $product->id);
                } else {
                    $available = (int) $product->stock;
                }
            }
            $givingNow = min($ordered, $available);
            $backOrderQty = max(0, $ordered - $available);
            $itemQuantities[] = [
                'item' => $invItem,
                'giving_now' => $givingNow,
                'line_total' => $givingNow * (float) $invItem->unit_price,
                'back_order_qty' => $backOrderQty,
                'product' => $product,
            ];
            if ($backOrderQty > 0 && $product) {
                $backOrdersToCreate[] = [
                    'invoice_item_id' => $invItem->id,
                    'product_id' => $product->id,
                    'item_name' => $invItem->item_name,
                    'unit' => $invItem->unit,
                    'unit_price' => $invItem->unit_price,
                    'quantity_pending' => $backOrderQty,
                ];
            }
        }

        $orderSubtotal = array_sum(array_column($itemQuantities, 'line_total'));

        $order = DB::transaction(function () use ($invoice, $itemQuantities, $backOrdersToCreate, $orderSubtotal) {
            $order = Order::create([
                'invoice_number' => $invoice->invoice_number,
                'invoice_id' => $invoice->id,
                'user_id' => $invoice->user_id,
                'branch_user_id' => $invoice->branch_user_id,
                'subtotal' => $orderSubtotal,
                'total_bv' => 0,
                'total_pv' => 0,
                'payment_method' => 'invoice',
                'status' => Order::STATUS_PAID,
                'shipping_address' => $invoice->customer_address,
                'shipping_phone' => $invoice->customer_phone,
                'customer_name' => $invoice->customer_name,
                'kd_id' => null,
            ]);

            foreach ($itemQuantities as $index => $row) {
                $givingNow = $row['giving_now'];
                if ($givingNow <= 0) {
                    continue;
                }
                $invItem = $row['item'];
                $itemCode = 'INV-'.$invoice->id.'-'.($index + 1);
                OrderItem::create([
                    'order_id' => $order->id,
                    'item_code' => $itemCode,
                    'product_name' => $invItem->item_name,
                    'quantity' => (int) round($givingNow, 2),
                    'unit_price' => $invItem->unit_price,
                    'line_total' => $row['line_total'],
                    'bv' => 0,
                    'pv' => 0,
                ]);
            }

            foreach ($backOrdersToCreate as $bo) {
                BackOrder::create([
                    'invoice_id' => $invoice->id,
                    'invoice_item_id' => $bo['invoice_item_id'],
                    'user_id' => $invoice->user_id,
                    'product_id' => $bo['product_id'],
                    'item_name' => $bo['item_name'],
                    'unit' => $bo['unit'],
                    'unit_price' => $bo['unit_price'],
                    'quantity_pending' => $bo['quantity_pending'],
                    'quantity_fulfilled' => 0,
                    'status' => BackOrder::STATUS_PENDING,
                ]);
            }

            return $order;
        });

        $backOrderCount = count($backOrdersToCreate);
        $msg = 'Invoice moved to dispatch. You can now process it.';
        if ($backOrderCount > 0) {
            $msg .= ' '.$backOrderCount.' back order(s) saved against the customer for when you receive more stock.';
        }

        return redirect()
            ->route('admin.dispatch.orders.show', $order)
            ->with('success', $msg);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only view invoices for your customers.');
            }
        }
        $invoice->load('items', 'user');
        $products = Product::where('is_active', true)->get();
        $productsByName = $products->keyBy(fn ($p) => $p->display_name);
        $outOfStockItemIds = [];
        $inStockItemIds = [];
        foreach ($invoice->items as $item) {
            $product = $this->findProductForItem($products, $item->item_name);
            if ($product) {
                if ((int) $product->stock <= 0) {
                    $outOfStockItemIds[] = $item->id;
                } else {
                    $inStockItemIds[] = $item->id;
                }
            }
        }
        $logoPath = public_path('images/logo.png');
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
            'outOfStockItemIds' => $outOfStockItemIds,
            'inStockItemIds' => $inStockItemIds,
        ]);

        return $pdf->download('invoice-'.$invoice->invoice_number.'.pdf');
    }

    public function approve(Request $request, Invoice $invoice)
    {
        $user = $request->user();
        if (! $user || ! $invoice->user_id) {
            abort(403, 'You cannot approve this invoice.');
        }
        if ($invoice->is_approved) {
            return back()->with('error', 'This invoice has already been approved.');
        }
        if (Order::where('invoice_id', $invoice->id)->exists()) {
            return back()->with('error', 'This invoice has already been moved to an order.');
        }

        $invoice->load('items', 'user.role');
        $invoiceUser = $invoice->user;
        $invoiceUserRole = $invoiceUser?->role?->name;
        $invoiceUserCreatedBy = (int) ($invoiceUser?->created_by_user_id ?? 0);

        $approverRole = $user->role?->name;
        $canApprove = false;
        $approvalType = null; // 'super_admin_hq' | 'hq_own' | 'hq_child' | 'branch_child' | 'service_center_child'

        if ($user->isSuperAdmin() && $invoiceUserRole === 'headquarters') {
            $canApprove = true;
            $approvalType = 'super_admin_hq';
        } elseif ($approverRole === 'headquarters' && (int) $invoice->user_id === (int) $user->id) {
            $canApprove = true;
            $approvalType = 'hq_own';
        } elseif ($approverRole === 'headquarters' && $invoiceUserCreatedBy === (int) $user->id && in_array($invoiceUserRole, ['branch', 'annex', 'service_center'], true)) {
            $canApprove = true;
            $approvalType = 'hq_child';
        } elseif ($approverRole === 'branch' && $invoiceUserCreatedBy === (int) $user->id && in_array($invoiceUserRole, ['annex', 'service_center'], true)) {
            $canApprove = true;
            $approvalType = 'branch_child';
        } elseif ($approverRole === 'service_center' && $invoiceUserCreatedBy === (int) $user->id && $invoiceUserRole === 'annex') {
            if (! $user->created_by_user_id) {
                abort(403, 'Service Center must be linked to a Branch to approve invoices.');
            }
            $canApprove = true;
            $approvalType = 'service_center_child';
        }

        if (! $canApprove) {
            abort(403, 'You cannot approve this invoice.');
        }

        // Determine wallet transfer: debit sender (invoice user), credit approver
        $sender = $invoiceUser;
        $receiver = $user;
        $amount = (float) ($invoice->total ?? 0);
        if ($amount <= 0) {
            return back()->with('error', 'Invoice total is zero. Cannot perform wallet transfer.');
        }
        if ($sender && $sender->id === $receiver->id) {
            return back()->with('error', 'Sender and approver are the same account. Wallet transfer not allowed.');
        }
        if (! $sender) {
            return back()->with('error', 'Invoice sender account not found.');
        }
        if (($sender->wallet_balance ?? 0) < $amount) {
            return back()->with('error', 'Sender does not have enough wallet balance to approve this invoice.');
        }

        $products = Product::where('is_active', true)->get();
        $productsByName = $products->keyBy(fn ($p) => $p->display_name);

        $stockSource = null; // 'main' | ['hq', $userId] | ['branch', $userId] | ['service_center', $userId]
        if ($approvalType === 'super_admin_hq' || $approvalType === 'hq_own') {
            $stockSource = 'main';
        } elseif ($approvalType === 'hq_child' && $invoiceUserRole === 'branch') {
            $stockSource = ['hq', $user->id];
        } elseif ($approvalType === 'hq_child') {
            $stockSource = ['hq', $user->id];
        } elseif ($approvalType === 'branch_child') {
            // Branch approves SC or Annex: deduct from Branch, credit to SC or Annex
            $stockSource = ['branch', $user->id];
        } elseif ($approvalType === 'service_center_child') {
            // SC approves Annex: deduct from Service Center, credit to Annex
            $stockSource = ['service_center', $user->id];
        } else {
            $stockSource = ['branch', (int) $user->created_by_user_id];
        }

        $stockUpdates = [];
        $backOrdersToCreate = [];
        foreach ($invoice->items as $item) {
            $product = $this->findProductForItem($products, $item->item_name);
            if (! $product) {
                return back()->with('error', "Product '{$item->item_name}' not found.");
            }
            $ordered = (float) $item->quantity;
            $available = 0;
            if ($stockSource === 'main') {
                $available = (int) $product->stock;
            } elseif ($stockSource[0] === 'hq') {
                $available = HeadquartersStock::getQuantity($stockSource[1], (int) $product->id);
            } elseif ($stockSource[0] === 'service_center') {
                $available = ServiceCenterStock::getQuantity($stockSource[1], (int) $product->id);
            } else {
                $available = BranchStock::getQuantity($stockSource[1], (int) $product->id);
            }
            $givingNow = min($ordered, $available);
            $backOrderQty = max(0, $ordered - $available);
            $stockUpdates[] = [
                'product' => $product,
                'quantity' => $givingNow,
                'item' => $item,
            ];
            if ($backOrderQty > 0) {
                $backOrdersToCreate[] = [
                    'invoice_item_id' => $item->id,
                    'product_id' => $product->id,
                    'item_name' => $item->item_name,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'quantity_pending' => $backOrderQty,
                ];
            }
        }

        $headquartersUserId = null;
        if ($approvalType === 'super_admin_hq' || $approvalType === 'hq_own') {
            $headquartersUserId = $approvalType === 'hq_own' ? $user->id : (int) $invoice->user_id;
        }

        try {
            DB::transaction(function () use ($invoice, $stockSource, $stockUpdates, $backOrdersToCreate, $headquartersUserId, $approvalType, $sender, $receiver, $amount) {
                if ($approvalType === 'super_admin_hq' || $approvalType === 'hq_own') {
                    foreach ($stockUpdates as $update) {
                        $product = $update['product'];
                        $qty = (int) $update['quantity'];
                        if ($qty <= 0) {
                            continue;
                        }
                        $product->decrement('stock', $qty);
                        HeadquartersStock::incrementStock($headquartersUserId, $product->id, $qty);
                    }
                } else {
                    foreach ($stockUpdates as $update) {
                        $product = $update['product'];
                        $qty = (int) $update['quantity'];
                        if ($qty <= 0) {
                            continue;
                        }
                        if ($stockSource[0] === 'hq') {
                            $ok = HeadquartersStock::decrementStock($stockSource[1], $product->id, $qty);
                            if (! $ok) {
                                throw new \RuntimeException("Insufficient HQ stock for {$product->display_name}.");
                            }
                        } elseif ($stockSource[0] === 'service_center') {
                            $ok = ServiceCenterStock::decrementStock($stockSource[1], $product->id, $qty);
                            if (! $ok) {
                                throw new \RuntimeException("Insufficient Service Center stock for {$product->display_name}.");
                            }
                        } else {
                            $ok = BranchStock::decrementStock($stockSource[1], $product->id, $qty);
                            if (! $ok) {
                                throw new \RuntimeException("Insufficient branch stock for {$product->display_name}.");
                            }
                        }
                    }
                }

                $createOrder = in_array($approvalType, ['hq_child', 'branch_child', 'service_center_child'], true);
                if ($createOrder) {
                    $orderSubtotal = 0;
                    foreach ($stockUpdates as $u) {
                        $orderSubtotal += $u['quantity'] * (float) $u['item']->unit_price;
                    }
                    // Order's branch_user_id = stock holder (customer) for Branch/SC approvals so delivery deduction uses correct table
                    $orderBranchUserId = in_array($approvalType, ['branch_child', 'service_center_child'], true)
                        ? (int) $invoice->user_id
                        : ($invoice->branch_user_id ?? $invoice->user_id);
                    $order = Order::create([
                        'invoice_number' => $invoice->invoice_number,
                        'invoice_id' => $invoice->id,
                        'user_id' => $invoice->user_id,
                        'branch_user_id' => $orderBranchUserId,
                        'subtotal' => $orderSubtotal,
                        'total_bv' => 0,
                        'total_pv' => 0,
                        'payment_method' => 'invoice',
                        'status' => Order::STATUS_PAID,
                        'shipping_address' => $invoice->customer_address,
                        'shipping_phone' => $invoice->customer_phone,
                        'customer_name' => $invoice->customer_name,
                        'kd_id' => null,
                    ]);
                    foreach ($stockUpdates as $index => $row) {
                        $givingNow = $row['quantity'];
                        if ($givingNow <= 0) {
                            continue;
                        }
                        $invItem = $row['item'];
                        $itemCode = 'INV-'.$invoice->id.'-'.($index + 1);
                        OrderItem::create([
                            'order_id' => $order->id,
                            'item_code' => $itemCode,
                            'product_name' => $invItem->item_name,
                            'quantity' => (int) round($givingNow, 2),
                            'unit_price' => $invItem->unit_price,
                            'line_total' => $givingNow * (float) $invItem->unit_price,
                            'bv' => 0,
                            'pv' => 0,
                        ]);
                    }
                    // HQ approving Branch: deduct from HQ, add to Branch
                    if ($approvalType === 'hq_child' && $invoice->user?->role?->name === 'branch') {
                        $branchUserId = (int) ($invoice->branch_user_id ?? $invoice->user_id);
                        foreach ($stockUpdates as $u) {
                            $qty = (int) $u['quantity'];
                            if ($qty > 0) {
                                BranchStock::incrementStock($branchUserId, $u['product']->id, $qty);
                            }
                        }
                    }
                    // Branch approving Service Center: deduct from Branch, add to Service Center
                    if ($approvalType === 'branch_child' && $invoice->user?->role?->name === 'service_center') {
                        $scUserId = (int) $invoice->user_id;
                        foreach ($stockUpdates as $u) {
                            $qty = (int) $u['quantity'];
                            if ($qty > 0) {
                                ServiceCenterStock::incrementStock($scUserId, $u['product']->id, $qty);
                            }
                        }
                    }
                    // Branch approving Annex: deduct from Branch, add to Annex
                    if ($approvalType === 'branch_child' && $invoice->user?->role?->name === 'annex') {
                        $annexUserId = (int) $invoice->user_id;
                        foreach ($stockUpdates as $u) {
                            $qty = (int) $u['quantity'];
                            if ($qty > 0) {
                                AnnexStock::incrementStock($annexUserId, $u['product']->id, $qty);
                            }
                        }
                    }
                    // Service Center approving Annex: deduct from SC, add to Annex
                    if ($approvalType === 'service_center_child' && $invoice->user?->role?->name === 'annex') {
                        $annexUserId = (int) $invoice->user_id;
                        foreach ($stockUpdates as $u) {
                            $qty = (int) $u['quantity'];
                            if ($qty > 0) {
                                AnnexStock::incrementStock($annexUserId, $u['product']->id, $qty);
                            }
                        }
                    }
                }

                foreach ($backOrdersToCreate as $bo) {
                    BackOrder::create([
                        'invoice_id' => $invoice->id,
                        'invoice_item_id' => $bo['invoice_item_id'],
                        'user_id' => $invoice->user_id,
                        'product_id' => $bo['product_id'],
                        'item_name' => $bo['item_name'],
                        'unit' => $bo['unit'],
                        'unit_price' => $bo['unit_price'],
                        'quantity_pending' => $bo['quantity_pending'],
                        'quantity_fulfilled' => 0,
                        'status' => BackOrder::STATUS_PENDING,
                    ]);
                }

                // Wallet transfer: debit sender, credit approver
                $sender->refresh();
                if (($sender->wallet_balance ?? 0) < $amount) {
                    throw new \RuntimeException('Sender does not have enough wallet balance to approve this invoice.');
                }
                $sender->decrement('wallet_balance', $amount);
                $senderBalanceAfter = (float) $sender->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $sender->id,
                    'type' => WalletTransaction::TYPE_DEBIT,
                    'amount' => $amount,
                    'balance_after' => $senderBalanceAfter,
                    'reference' => 'Invoice #'.$invoice->invoice_number.' approval (sent)',
                ]);

                $receiver->increment('wallet_balance', $amount);
                $receiverBalanceAfter = (float) $receiver->fresh()->wallet_balance;
                WalletTransaction::create([
                    'user_id' => $receiver->id,
                    'type' => WalletTransaction::TYPE_CREDIT,
                    'amount' => $amount,
                    'balance_after' => $receiverBalanceAfter,
                    'reference' => 'Invoice #'.$invoice->invoice_number.' approval (received)',
                ]);

                $invoice->update(['is_approved' => true, 'approved_at' => now()]);
            });
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage() ?: 'Approval failed. Please try again.');
        }

        $backOrderCount = count($backOrdersToCreate);
        $msg = 'Invoice approved successfully.';
        if ($approvalType === 'super_admin_hq' || $approvalType === 'hq_own') {
            $msg .= ' Stock has been deducted from main inventory and added to headquarters.';
        } else {
            $msg .= ' Stock has been deducted and an order has been created for the customer.';
        }
        if ($backOrderCount > 0) {
            $msg .= ' '.$backOrderCount.' back order(s) saved for when you receive more stock.';
        }

        return back()->with('success', $msg);
    }

    protected function generateInvoiceNumber(): string
    {
        $nextId = (int) Invoice::max('id') + 1;

        return 'INV-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    /** Get the HQ user id whose stock is used when fulfilling an invoice for this customer (Annex or Service Center). */
    private function getFulfillingHqIdForInvoiceCustomer($user): int
    {
        if (! $user || ! $user->created_by_user_id) {
            return 0;
        }
        $parent = User::with('role')->find($user->created_by_user_id);
        if (! $parent) {
            return 0;
        }
        if ($parent->role?->name === 'branch' && $parent->created_by_user_id) {
            return (int) $parent->created_by_user_id;
        }
        if ($parent->role?->name === 'service_center' && $parent->created_by_user_id) {
            $branch = User::with('role')->find($parent->created_by_user_id);

            return ($branch && $branch->role?->name === 'branch' && $branch->created_by_user_id)
                ? (int) $branch->created_by_user_id
                : 0;
        }

        return 0;
    }

    /**
     * Try to find a Product model that corresponds to an invoice item name.
     * Matches by `display_name`, `name`, `item_code` using exact and
     * case-insensitive comparisons, strips parenthetical pack sizes,
     * and falls back to a contains-based fuzzy match.
     */
    private function findProductForItem($products, $itemName)
    {
        $itemName = trim((string) $itemName);
        if ($itemName === '') {
            return null;
        }

        // Exact display_name
        $prod = $products->first(fn ($p) => ($p->display_name ?? null) === $itemName);
        if ($prod) {
            return $prod;
        }

        // Exact name
        $prod = $products->first(fn ($p) => ($p->name ?? null) === $itemName);
        if ($prod) {
            return $prod;
        }

        // Exact item_code
        $prod = $products->first(fn ($p) => isset($p->item_code) && $p->item_code === $itemName);
        if ($prod) {
            return $prod;
        }

        $lower = strtolower($itemName);
        // Case-insensitive exact matches
        $prod = $products->first(fn ($p) => strtolower($p->display_name ?? '') === $lower || strtolower($p->name ?? '') === $lower || (isset($p->item_code) && strtolower($p->item_code) === $lower));
        if ($prod) {
            return $prod;
        }

        // Remove parenthetical suffixes: "Name (Pack)" -> "Name"
        $noParens = trim(preg_replace('/\s*\([^)]*\)/', '', $itemName));
        if ($noParens !== $itemName) {
            $prod = $products->first(fn ($p) => ($p->display_name ?? '') === $noParens || ($p->name ?? '') === $noParens || strtolower($p->name ?? '') === strtolower($noParens));
            if ($prod) {
                return $prod;
            }
        }

        // Fuzzy contains-based match
        $prod = $products->first(fn ($p) => str_contains(strtolower($p->display_name ?? ''), $lower) || str_contains(strtolower($p->name ?? ''), $lower));

        return $prod ?: null;
    }
}
