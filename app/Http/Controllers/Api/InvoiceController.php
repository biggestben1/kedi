<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
