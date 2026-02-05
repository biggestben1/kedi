<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuperAdminInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $customerIds = $resellerOnly ? $request->user()->createdUsers()->pluck('id')->all() : null;

        $invoices = Invoice::with('user')
            ->when($customerIds !== null, function ($q) use ($customerIds) {
                $q->whereIn('user_id', $customerIds);
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

        return view('admin.invoices.index', [
            'invoices' => $invoices,
            'statusFilter' => $request->query('status'),
            'search' => $request->query('q'),
        ]);
    }

    public function create(Request $request)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        $users = $resellerOnly
            ? $request->user()->createdUsers()->with('role')->orderBy('name')->get()
            : User::with('role')->orderBy('name')->get();
        $selectedUser = null;
        $products = collect();
        if ($request->filled('user_id')) {
            $selectedUser = User::find($request->query('user_id'));
            if ($selectedUser && (! $resellerOnly || $selectedUser->created_by_user_id === $request->user()->id)) {
                $products = Product::where('is_active', true)->orderBy('name')->get();
            } else {
                $selectedUser = null;
            }
        }
        return view('admin.invoices.create', [
            'users' => $users,
            'selectedUser' => $selectedUser,
            'products' => $products,
        ]);
    }

    public function store(Request $request)
    {
        $useProducts = $request->boolean('use_product_quantities');

        if ($useProducts) {
            $data = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
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
                'use_product_quantities' => ['nullable'],
                'product_quantities' => ['required', 'array'],
                'product_quantities.*' => ['numeric', 'min:0'],
            ]);
            $user = User::findOrFail($data['user_id']);
            $productQuantities = array_filter($data['product_quantities'] ?? [], fn ($q) => (float) $q > 0);
            if (empty($productQuantities)) {
                return redirect()->back()->withInput()->withErrors(['product_quantities' => 'Enter quantity for at least one product.']);
            }
            $productIds = array_keys($productQuantities);
            $products = Product::whereIn('id', $productIds)->get()->keyBy('id');
            $items = [];
            foreach ($productQuantities as $productId => $qty) {
                $product = $products->get($productId);
                if (!$product) {
                    continue;
                }
                $qty = (float) $qty;
                $unitPrice = $product->getPriceForUser($user);
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
        if ($resellerOnly && ! empty($data['user_id']) && ! in_array((int) $data['user_id'], $request->user()->createdUsers()->pluck('id')->all(), true)) {
            return redirect()->back()->withInput()->withErrors(['user_id' => 'You can only create invoices for your customers.']);
        }

        $data['invoice_number'] = $this->generateInvoiceNumber();

        $subtotal = array_sum(array_column($items, 'line_total'));
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = $subtotal + $tax - $discount;

        $invoice = null;
        DB::transaction(function () use ($data, $items, $subtotal, $tax, $discount, $total, &$invoice) {
            $invoice = Invoice::create([
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
        });

        return redirect()
            ->route('admin.invoices.index')
            ->with('success', 'Invoice created successfully.')
            ->with('created_invoice_id', $invoice->id);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your customers.');
            }
        }
        $invoice->load('items', 'user');
        $users = $resellerOnly
            ? $request->user()->createdUsers()->with('role')->orderBy('name')->get()
            : User::with('role')->orderBy('name')->get();
        return view('admin.invoices.edit', [
            'invoice' => $invoice,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $resellerOnly = $request->user()?->role?->name === 'reseller';
        if ($resellerOnly) {
            $customerIds = $request->user()->createdUsers()->pluck('id')->all();
            if (! $invoice->user_id || ! in_array((int) $invoice->user_id, $customerIds, true)) {
                abort(403, 'Access denied. You can only edit invoices for your customers.');
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
                if (!empty($item['id'])) {
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
        if ($resellerOnly && $invoice->user_id && ! in_array((int) $invoice->user_id, $request->user()->createdUsers()->pluck('id')->all(), true)) {
            abort(403, 'Access denied. You can only delete invoices for your customers.');
        }
        $invoice->items()->delete();
        $invoice->delete();
        return redirect()->route('admin.invoices.index')->with('success', 'Invoice deleted successfully.');
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
        $logoPath = public_path('images/logo.png');
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ]);
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    protected function generateInvoiceNumber(): string
    {
        $nextId = (int) Invoice::max('id') + 1;
        return 'INV-' . str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }
}
