<?php

namespace App\Http\Controllers;

use App\Models\FactoryInvoice;
use App\Models\FactoryInvoiceItem;
use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SuperAdminInStockController extends Controller
{
    public function index(Request $request)
    {
        $invoices = FactoryInvoice::with('items.product')
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->when($request->query('status'), function ($q, $status) {
                $q->whereHas('items', fn ($i) => $i->where('status', $status));
            })
            ->paginate(20)
            ->withQueryString();

        return view('admin.in-stock.index', [
            'invoices' => $invoices,
            'statusFilter' => $request->query('status'),
            'statusOptions' => FactoryInvoice::statusOptions(),
        ]);
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.in-stock.create', [
            'products' => $products,
            'statusOptions' => FactoryInvoice::statusOptions(),
            'nextInvoiceNumber' => $this->generateInvoiceNumber(),
        ]);
    }

    protected function generateInvoiceNumber(): string
    {
        $nextId = (int) FactoryInvoice::max('id') + 1;

        return 'FI-'.str_pad((string) $nextId, 6, '0', STR_PAD_LEFT);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'factory_name' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.status' => ['required', 'string', Rule::in(array_keys(FactoryInvoice::statusOptions()))],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $invoiceNumber = $this->generateInvoiceNumber();

        $invoice = DB::transaction(function () use ($data, $invoiceNumber) {
            $invoice = FactoryInvoice::create([
                'invoice_number' => $invoiceNumber,
                'factory_name' => $data['factory_name'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $i => $row) {
                $product = Product::find($row['product_id']);
                $qty = (float) $row['quantity'];
                $cost = (float) ($row['cost_price'] ?? 0);
                FactoryInvoiceItem::create([
                    'factory_invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'item_code' => $product->item_code ?? null,
                    'product_name' => $product->display_name ?? $product->name,
                    'quantity' => (int) ceil($qty),
                    'status' => $row['status'],
                    'cost_price' => $cost,
                    'line_total' => $cost * $qty,
                ]);
            }

            return $invoice;
        });

        return redirect()->route('admin.in-stock.show', $invoice)
            ->with('success', 'Factory invoice created successfully.');
    }

    public function show(FactoryInvoice $inStock)
    {
        $inStock->load('items.product');

        return view('admin.in-stock.show', [
            'invoice' => $inStock,
            'statusOptions' => FactoryInvoice::statusOptions(),
        ]);
    }

    public function edit(FactoryInvoice $inStock)
    {
        $inStock->load('items.product');
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('admin.in-stock.edit', [
            'invoice' => $inStock,
            'products' => $products,
            'statusOptions' => FactoryInvoice::statusOptions(),
        ]);
    }

    public function update(Request $request, FactoryInvoice $inStock)
    {
        $data = $request->validate([
            'invoice_number' => ['required', 'string', 'max:100', Rule::unique('factory_invoices', 'invoice_number')->ignore($inStock->id)],
            'factory_name' => ['nullable', 'string', 'max:255'],
            'invoice_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'integer', 'exists:factory_invoice_items,id'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.status' => ['required', 'string', Rule::in(array_keys(FactoryInvoice::statusOptions()))],
            'items.*.cost_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::transaction(function () use ($data, $inStock) {
            $inStock->update([
                'invoice_number' => $data['invoice_number'],
                'factory_name' => $data['factory_name'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'notes' => $data['notes'] ?? null,
            ]);

            $keepIds = [];
            foreach ($data['items'] as $row) {
                $product = Product::find($row['product_id']);
                $qty = (float) $row['quantity'];
                $cost = (float) ($row['cost_price'] ?? 0);
                $attrs = [
                    'product_id' => $product->id,
                    'item_code' => $product->item_code ?? null,
                    'product_name' => $product->display_name ?? $product->name,
                    'quantity' => (int) ceil($qty),
                    'status' => $row['status'],
                    'cost_price' => $cost,
                    'line_total' => $cost * $qty,
                ];
                if (! empty($row['id'])) {
                    $item = FactoryInvoiceItem::where('factory_invoice_id', $inStock->id)->find($row['id']);
                    if ($item) {
                        $item->update($attrs);
                        $keepIds[] = $item->id;

                        continue;
                    }
                }
                $newItem = FactoryInvoiceItem::create([
                    'factory_invoice_id' => $inStock->id,
                    ...$attrs,
                ]);
                $keepIds[] = $newItem->id;
            }
            $inStock->items()->whereNotIn('id', $keepIds)->delete();
        });

        return redirect()->route('admin.in-stock.show', $inStock)
            ->with('success', 'Factory invoice updated successfully.');
    }

    public function destroy(FactoryInvoice $inStock)
    {
        $inStock->items()->delete();
        $inStock->delete();

        return redirect()->route('admin.in-stock.index')
            ->with('success', 'Factory invoice deleted.');
    }

    /** Add factory invoice quantities to product stock (main inventory). */
    public function addToStock(FactoryInvoice $inStock)
    {
        // Only allow adding once to avoid double-counting
        if ($inStock->stock_added_at) {
            return redirect()->route('admin.in-stock.show', $inStock)
                ->with('error', 'Stock has already been added from this invoice on '.$inStock->stock_added_at->format('M d, Y H:i').'.');
        }

        $inStock->load('items.product');

        DB::transaction(function () use ($inStock) {
            foreach ($inStock->items as $item) {
                if ($item->quantity <= 0) {
                    continue;
                }

                // Prefer the related product; fall back to item_code lookup
                $product = $item->product;
                if (! $product && $item->item_code) {
                    $product = Product::where('item_code', $item->item_code)->first();
                }

                if ($product) {
                    $product->increment('stock', (int) $item->quantity);
                    // Mark as brought since it has been added to stock
                    $item->update(['is_brought' => true, 'product_id' => $product->id]);
                }
            }

            $inStock->update(['stock_added_at' => now()]);
        });

        return redirect()->route('admin.in-stock.show', $inStock)
            ->with('success', 'Stock added from this factory invoice into product stock.');
    }

    /** Update which items were brought (received). */
    public function updateBrought(Request $request, FactoryInvoice $inStock)
    {
        $broughtIds = $request->input('brought', []);
        if (! is_array($broughtIds)) {
            $broughtIds = [];
        }
        $broughtIds = array_map('intval', $broughtIds);

        $inStock->load('items.product');

        // Update brought flags for this invoice's items
        foreach ($inStock->items as $item) {
            $nowBrought = in_array($item->id, $broughtIds, true);
            $item->update(['is_brought' => $nowBrought]);
        }

        return redirect()->route('admin.in-stock.show', $inStock)
            ->with('success', 'Brought status updated.');
    }

    /** Generate PDF for the factory invoice. */
    public function pdf(FactoryInvoice $inStock)
    {
        $inStock->load('items.product');

        $pdf = Pdf::loadView('admin.in-stock.pdf', [
            'invoice' => $inStock,
            'statusOptions' => FactoryInvoice::statusOptions(),
        ]);

        return $pdf->download('factory-invoice-'.$inStock->invoice_number.'.pdf');
    }
}
