<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // Redirect admin roles to admin panel; they should not use the store-front "My Orders" page
        // EXCEPT if they are viewing their drafts
        $role = $request->user()?->role?->name;
        $statusFilter = $request->query('status');
        if (in_array($role, ['branch', 'headquarters', 'service_center', 'annex', 'dispatch', 'accountant'], true) && $statusFilter !== 'draft') {
            return redirect()->route('admin');
        }

        $query = $request->user()
            ->orders()
            ->with('items')
            ->orderByDesc('created_at');

        $statusFilter = $request->query('status');
        if ($statusFilter === 'draft') {
            $query->where('status', Order::STATUS_DRAFT);
        }

        $orders = $query->paginate(15)->withQueryString();
        $request->session()->forget('placed_order_ids');
        $cartCount = array_sum($request->session()->get('cart', []));
        $draftCount = $request->user()->orders()->where('status', Order::STATUS_DRAFT)->count();

        $draftTotal = 0;
        $walletBalance = 0;
        if ($statusFilter === 'draft') {
            $draftTotal = $request->user()->orders()->where('status', Order::STATUS_DRAFT)->sum('subtotal');
            $walletBalance = (float) ($request->user()->wallet_balance ?? 0);
        }

        return view('orders.index', [
            'orders' => $orders,
            'cartCount' => $cartCount,
            'statusFilter' => $statusFilter,
            'draftCount' => $draftCount,
            'draftTotal' => $draftTotal,
            'walletBalance' => $walletBalance,
        ]);
    }

    public function show(Request $request, Order $order)
    {
        $role = $request->user()?->role?->name;
        if (in_array($role, ['branch', 'headquarters', 'service_center', 'annex', 'dispatch', 'accountant'], true) && $order->status !== Order::STATUS_DRAFT) {
            return redirect()->route('admin');
        }
        //if ($order->user_id !== $request->user()->id) {
        //    abort(404);
       // }

        $order->load('items');
        $cartCount = array_sum($request->session()->get('cart', []));

        return view('orders.show', ['order' => $order, 'cartCount' => $cartCount]);
    }

    public function receipt(Request $request, Order $order)
    {
        if ($order->user_id != $request->user()->id) {
            abort(404);
        }
        if ($order->status === Order::STATUS_DRAFT) {
            return redirect()->route('orders.index')->with('error', 'Draft orders do not have a receipt.');
        }

        $order->load('user', 'items');

        $placedOrdersFull = collect();
        $placedOrderIds = session('placed_order_ids', []);
        if (count($placedOrderIds) > 1) {
            $placedOrdersFull = Order::with('user', 'items')
                ->whereIn('id', $placedOrderIds)
                ->where('user_id', $request->user()->id)
                ->orderBy('id')
                ->get();
        }

        return view('orders.receipt', [
            'order' => $order,
            'placedOrdersFull' => $placedOrdersFull,
        ]);
    }

    public function receiptPdf(Request $request, Order $order)
    {
        if (in_array($request->user()?->role?->name, ['branch', 'headquarters', 'annex', 'dispatch', 'accountant'], true)) {
            return redirect()->route('admin');
        }
        if ($order->user_id !== $request->user()->id) {
            abort(404);
        }
        if ($order->status === Order::STATUS_DRAFT) {
            abort(404);
        }

        $order->load('user', 'items');

        $pdf = Pdf::loadView('orders.receipt-pdf', ['order' => $order]);
        $filename = 'receipt-' . ($order->invoice_number ?? 'ORD-' . $order->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Create PDF for selected orders.
     */
    public function receiptPdfSelected(Request $request)
    {
        $ids = $request->input('order_ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $ids))));
        } elseif (is_array($ids)) {
            $ids = array_filter(array_map('intval', $ids));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->route('orders.index')->with('error', 'Select at least one order to create PDF.');
        }

        $orders = Order::with('user', 'items')
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->where('status', '!=', Order::STATUS_DRAFT)
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('orders.index')->with('error', 'No valid orders selected. Draft orders cannot be included.');
        }

        $pdf = Pdf::loadView('orders.receipt-pdf-multi', ['orders' => $orders]);
        $filename = 'receipts-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Download selected orders as CSV (same info as PDF receipt).
     */
    public function exportCsv(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        if (in_array($request->user()?->role?->name, ['branch', 'headquarters', 'annex', 'dispatch', 'accountant'], true)) {
            return redirect()->route('admin');
        }
        $ids = $request->input('order_ids', []);
        if (is_string($ids)) {
            $ids = array_filter(array_map('intval', array_map('trim', explode(',', $ids))));
        } elseif (is_array($ids)) {
            $ids = array_filter(array_map('intval', $ids));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->route('orders.index')->with('error', 'Select at least one order to download CSV.');
        }

        $orders = Order::with('user', 'items')
            ->where('user_id', $request->user()->id)
            ->whereIn('id', $ids)
            ->where('status', '!=', Order::STATUS_DRAFT)
            ->orderBy('id')
            ->get();

        if ($orders->isEmpty()) {
            return redirect()->route('orders.index')->with('error', 'No valid orders selected. Draft orders cannot be included.');
        }

        $filename = 'orders-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Order #',
            'Date',
            'Payment',
            'KD No',
            'Customer Name',
            'Customer (Account) Name',
            'Customer (Account) Email',
            'Shipping Address',
            'Shipping City',
            'Shipping State',
            'Postal Code',
            'Shipping Phone',
            'Status',
            'Delivery',
            'Tracking #',
            'Item Code',
            'Product Name',
            'Qty',
            'Unit Price',
            'Line Total',
            'Order Subtotal',
        ];

        return response()->streamDownload(function () use ($orders, $headers) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($orders as $order) {
                $orderNum = $order->invoice_number ?? 'ORD-' . $order->id;
                $payment = $order->payment_method === 'wallet' ? 'Wallet' : 'Pay on Delivery';
                $kdNo = $order->kd_id ?? '—';
                $custName = $order->customer_name ?? '—';
                $accountName = $order->user?->name ?? '—';
                $accountEmail = $order->user?->email ?? '—';
                $delivery = $order->delivered_at ? 'Delivered' : ($order->status === Order::STATUS_DRAFT ? '—' : 'Not delivered');
                $subtotal = $order->subtotal;

                foreach ($order->items as $item) {
                    fputcsv($out, [
                        $orderNum,
                        $order->created_at->format('Y-m-d H:i'),
                        $payment,
                        $kdNo,
                        $custName,
                        $accountName,
                        $accountEmail,
                        $order->shipping_address ?? '',
                        $order->shipping_city ?? '',
                        $order->shipping_state ?? '',
                        $order->shipping_postal_code ?? '',
                        $order->shipping_phone ?? '',
                        $order->status,
                        $delivery,
                        $order->tracking_number ?? '',
                        $item->item_code ?? '',
                        $item->product_name ?? '',
                        $item->quantity,
                        $item->unit_price,
                        $item->line_total,
                        $subtotal,
                    ]);
                }
                if ($order->items->isEmpty()) {
                    fputcsv($out, [
                        $orderNum,
                        $order->created_at->format('Y-m-d H:i'),
                        $payment,
                        $kdNo,
                        $custName,
                        $accountName,
                        $accountEmail,
                        $order->shipping_address ?? '',
                        $order->shipping_city ?? '',
                        $order->shipping_state ?? '',
                        $order->shipping_postal_code ?? '',
                        $order->shipping_phone ?? '',
                        $order->status,
                        $delivery,
                        $order->tracking_number ?? '',
                        '',
                        '',
                        '',
                        '',
                        '',
                        $subtotal,
                    ]);
                }
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * Download PDF of items added for supply.
     */
    public function addedItemsPdf(Request $request)
    {
        if (in_array($request->user()?->role?->name, ['branch', 'headquarters', 'annex', 'dispatch', 'accountant'], true)) {
            return redirect()->route('admin');
        }
        $items = $request->session()->get('added_items', []);

        if (empty($items)) {
            return redirect()->route('orders.index')->with('error', 'No items to export. Add items for supply first.');
        }

        $total = collect($items)->sum('line_total');

        $pdf = Pdf::loadView('orders.added-items-pdf', [
            'items' => $items,
            'total' => $total,
        ]);
        $filename = 'items-for-supply-' . now()->format('Y-m-d-His') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Clear items for supply so user can select again.
     */
    public function clearAddedItems(Request $request)
    {
        if (in_array($request->user()?->role?->name, ['branch', 'headquarters', 'annex', 'dispatch', 'accountant'], true)) {
            return redirect()->route('admin');
        }
        $request->session()->forget('added_items');

        return redirect()->route('orders.index')->with('success', 'Cleared. You can select orders and add for supply again.');
    }
}
