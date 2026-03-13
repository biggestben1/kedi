<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $invoices = Invoice::with('items')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate($request->input('per_page', 15));

        $invoices->getCollection()->transform(fn (Invoice $inv) => $this->invoiceResource($inv));

        return response()->json($invoices);
    }

    public function show(Request $request, Invoice $invoice): JsonResponse
    {
        if ((int) $invoice->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $invoice->load('items');

        return response()->json(['data' => $this->invoiceResource($invoice)]);
    }

    public function pdf(Request $request, Invoice $invoice): StreamedResponse|JsonResponse
    {
        if ((int) $invoice->user_id !== (int) $request->user()->id) {
            return response()->json(['message' => 'Invoice not found.'], 404);
        }

        $invoice->load('items', 'user');
        $logoPath = public_path('images/logo.png');
        $pdf = Pdf::loadView('admin.invoices.pdf', [
            'invoice' => $invoice,
            'logoPath' => file_exists($logoPath) ? $logoPath : null,
        ]);

        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_address' => ['nullable', 'string'],
            'invoice_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:invoice_date'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.unit' => ['nullable', 'string', 'max:50'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $invoice = null;

        DB::transaction(function () use ($data, $request, &$invoice) {
            $subtotal = 0;
            $itemsData = [];
            foreach ($data['items'] as $item) {
                $qty = (float) $item['quantity'];
                $price = (float) $item['unit_price'];
                $lineTotal = $qty * $price;
                $subtotal += $lineTotal;
                $itemsData[] = array_merge($item, ['line_total' => $lineTotal]);
            }

            $tax = (float) ($data['tax'] ?? 0);
            $discount = (float) ($data['discount'] ?? 0);
            $total = $subtotal + $tax - $discount;

            $invoice = Invoice::create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'user_id' => $request->user()->id,
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'customer_address' => $data['customer_address'],
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'],
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => 'draft',
                'notes' => $data['notes'],
            ]);

            foreach ($itemsData as $index => $item) {
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

        return response()->json([
            'status' => 'success',
            'message' => 'Invoice created successfully.',
            'data' => $this->invoiceResource($invoice),
        ], 201);
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $lastInvoice = Invoice::where('invoice_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        } else {
            $nextNumber = '0001';
        }

        return $prefix . $nextNumber;
    }

    private function invoiceResource(Invoice $invoice): array
    {
        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'customer_phone' => $invoice->customer_phone,
            'invoice_date' => $invoice->invoice_date?->format('Y-m-d'),
            'due_date' => $invoice->due_date?->format('Y-m-d'),
            'subtotal' => (float) $invoice->subtotal,
            'tax' => (float) $invoice->tax,
            'discount' => (float) $invoice->discount,
            'total' => (float) $invoice->total,
            'status' => $invoice->status,
            'created_at' => $invoice->created_at->toIso8601String(),
            'items' => $invoice->items->map(fn ($i) => [
                'item_name' => $i->item_name,
                'quantity' => (float) $i->quantity,
                'unit_price' => (float) $i->unit_price,
                'line_total' => (float) $i->line_total,
            ])->all(),
        ];
    }
}
