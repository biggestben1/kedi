<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = Invoice::where('user_id', $request->user()->id)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $cartCount = array_sum($request->session()->get('cart', []));

        return view('invoices.index', [
            'invoices' => $invoices,
            'cartCount' => $cartCount,
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $cartCount = array_sum($request->session()->get('cart', []));

        $prefillQuantities = [];
        $addedItems = $request->session()->get('added_items', []);
        if (! empty($addedItems)) {
            foreach ($addedItems as $row) {
                $product = Product::where('item_code', $row['item_code'] ?? '')->where('is_active', true)->first();
                if ($product) {
                    $prefillQuantities[$product->id] = ($prefillQuantities[$product->id] ?? 0) + (int) ($row['quantity'] ?? 0);
                }
            }
            $request->session()->forget('added_items');
        }

        return view('invoices.create', [
            'user' => $user,
            'products' => $products,
            'cartCount' => $cartCount,
            'prefillQuantities' => $prefillQuantities,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $useProducts = $request->boolean('use_product_quantities');

        if ($useProducts) {
            $data = $request->validate([
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

        $data['invoice_number'] = $this->generateInvoiceNumber();
        $data['user_id'] = $user->id;

        $subtotal = array_sum(array_column($items, 'line_total'));
        $tax = (float) ($data['tax'] ?? 0);
        $discount = (float) ($data['discount'] ?? 0);
        $total = $subtotal + $tax - $discount;

        $invoice = null;
        DB::transaction(function () use ($data, $items, $subtotal, $tax, $discount, $total, $user, &$invoice) {
            $invoice = Invoice::create([
                'invoice_number' => $data['invoice_number'],
                'user_id' => $data['user_id'],
                'customer_name' => $data['customer_name'] ?? $user->name ?? null,
                'customer_email' => $data['customer_email'] ?? $user->email ?? null,
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
            ->route('invoices.index')
            ->with('success', 'Invoice created successfully.')
            ->with('created_invoice_id', $invoice->id);
    }

    protected function generateInvoiceNumber(): string
    {
        $lastInvoice = Invoice::orderByDesc('id')->first();
        $nextNum = $lastInvoice ? ((int) preg_replace('/[^0-9]/', '', $lastInvoice->invoice_number)) + 1 : 1;
        return 'INV-' . str_pad((string) $nextNum, 6, '0', STR_PAD_LEFT);
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        if ((int) $invoice->user_id !== (int) $request->user()->id) {
            abort(404);
        }

        $invoice->load('items', 'user');
        $logoPath = public_path('images/logo.png');
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ]);
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
