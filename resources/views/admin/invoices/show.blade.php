@extends('layouts.admin')

@section('title', 'View Invoice')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Invoice {{ $invoice->invoice_number }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.invoices.index') }}">Invoices</a></li>
                <li class="breadcrumb-item active" aria-current="page">View</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Invoice {{ $invoice->invoice_number }}</h3>
            <div class="d-flex gap-2">
                <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="fe fe-file-text me-1"></i>Download PDF</a>
                <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-sm btn-primary">Edit</a>
                @if($canApprove ?? false)
                    <form action="{{ route('admin.invoices.approve', $invoice) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Are you sure you want to approve this invoice? Stock will be deducted and an order will be created for the customer.');">
                            <i class="fe fe-check me-1"></i>Approve Invoice
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <label class="form-label text-muted small">Invoice Number</label>
                    <p class="mb-0 fw-semibold">{{ $invoice->invoice_number }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Invoice Date</label>
                    <p class="mb-0">{{ $invoice->invoice_date->format('M d, Y') }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Due Date</label>
                    <p class="mb-0">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</p>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-muted small">Status</label>
                    <p class="mb-0">
                        @if($invoice->status === 'draft')
                            <span class="badge bg-secondary">Draft</span>
                        @elseif($invoice->status === 'sent')
                            <span class="badge bg-info">Sent</span>
                        @elseif($invoice->status === 'paid')
                            <span class="badge bg-success">Paid</span>
                        @elseif($invoice->status === 'overdue')
                            <span class="badge bg-danger">Overdue</span>
                        @else
                            <span class="badge bg-warning text-dark">Cancelled</span>
                        @endif
                        @if($invoice->is_approved)
                            <span class="badge bg-success ms-1">Approved</span>
                        @endif
                        @if(isset($existingOrder) && $existingOrder)
                            <span class="badge bg-info ms-1">Moved to order</span>
                        @endif
                    </p>
                </div>
            </div>
            @if(isset($existingOrder) && $existingOrder)
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <a href="{{ route('admin.dispatch.orders.show', $existingOrder) }}" class="btn btn-sm btn-outline-success"><i class="fe fe-truck me-1"></i>View dispatch order</a>
                    </div>
                </div>
            @endif
            @if($invoice->is_approved && $invoice->approved_at)
                <div class="row g-3 mb-4">
                    <div class="col-md-12">
                        <label class="form-label text-muted small">Approved At</label>
                        <p class="mb-0">{{ $invoice->approved_at->format('M d, Y h:i A') }}</p>
                    </div>
                </div>
            @endif

            <div class="mb-4">
                <label class="form-label text-muted small">Bill To</label>
                <div class="border rounded p-3 bg-light">
                    @if($invoice->customer_name)<strong>{{ $invoice->customer_name }}</strong><br>@endif
                    @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
                    @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
                    @if($invoice->user?->kid)<strong>Kid:</strong> {{ $invoice->user->kid }}<br>@endif
                    @if($invoice->customer_address){!! nl2br(e($invoice->customer_address)) !!}@endif
                    @if(!$invoice->customer_name && !$invoice->customer_email && !$invoice->customer_phone && !$invoice->customer_address)
                        <span class="text-muted">—</span>
                    @endif
                </div>
            </div>

            <label class="form-label text-muted small">Items</label>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th class="text-end">Qty</th>
                            <th>Unit</th>
                            <th class="text-end">Unit Price</th>
                            <th class="text-end">Line Total</th>
                            <th class="text-end">In stock</th>
                            <th class="text-end">Giving now</th>
                            <th class="text-end">Back order</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $index => $item)
                            @php
                                $stockQty = $itemStock[$item->id] ?? null;
                                $givingNow = $itemGivingNow[$item->id] ?? 0;
                                $backOrderQty = $itemBackOrderQty[$item->id] ?? 0;
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    {{ $item->item_name }}
                                    @if($item->description)
                                        <br><small class="text-muted">{{ $item->description }}</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                                <td>{{ $item->unit ?? '—' }}</td>
                                <td class="text-end">₦{{ number_format($item->unit_price, 2) }}</td>
                                <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                                <td class="text-end">
                                    @if($stockQty !== null)
                                        <strong>{{ number_format($stockQty) }}</strong>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($givingNow > 0)
                                        <strong class="text-success">{{ number_format($givingNow) }}</strong>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($backOrderQty > 0)
                                        <strong class="text-warning">{{ number_format($backOrderQty) }}</strong>
                                    @else
                                        <span class="text-muted">0</span>
                                    @endif
                                </td>
                                <td>
                                    @if(in_array($item->id, $outOfStockItemIds ?? []))
                                        <span class="badge bg-warning text-dark">Back order</span>
                                    @elseif(in_array($item->id, $inStockItemIds ?? []))
                                        <span class="badge bg-success">In stock</span>
                                    @else
                                        <span class="badge bg-secondary">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(!empty($backOrderLines))
            <div class="mt-4">
                <label class="form-label text-muted small">Back order (shortfall saved against customer)</label>
                <p class="small text-muted mb-2">When you approve or move to dispatch, you give the &quot;Giving now&quot; quantity. The back order quantity is saved against {{ $invoice->user?->name ?? 'the customer' }} so you can fulfill when you receive more stock.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Ordered</th>
                                <th class="text-end">In stock</th>
                                <th class="text-end">Giving now</th>
                                <th class="text-end">Back order (saved)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($backOrderLines as $line)
                                <tr>
                                    <td>{{ $line->item_name }}</td>
                                    <td class="text-end">{{ number_format($line->ordered) }}</td>
                                    <td class="text-end">{{ number_format($line->in_stock) }}</td>
                                    <td class="text-end text-success">{{ number_format($line->giving_now) }}</td>
                                    <td class="text-end text-warning fw-semibold">{{ number_format($line->back_order_qty) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            <div class="row justify-content-end mt-3">
                <div class="col-md-4">
                    <table class="table table-sm mb-0">
                        <tr>
                            <td class="border-0 text-muted">Subtotal</td>
                            <td class="border-0 text-end">₦{{ number_format($invoice->subtotal, 2) }}</td>
                        </tr>
                        @if((float) $invoice->tax > 0)
                            <tr>
                                <td class="border-0 text-muted">Tax</td>
                                <td class="border-0 text-end">₦{{ number_format($invoice->tax, 2) }}</td>
                            </tr>
                        @endif
                        @if((float) $invoice->discount > 0)
                            <tr>
                                <td class="border-0 text-muted">Discount</td>
                                <td class="border-0 text-end">-₦{{ number_format($invoice->discount, 2) }}</td>
                            </tr>
                        @endif
                        <tr>
                            <td class="border-0 fw-bold">Total</td>
                            <td class="border-0 text-end fw-bold">₦{{ number_format($invoice->total, 2) }}</td>
                        </tr>
                    </table>
                </div>
            </div>

            @if($invoice->notes)
                <div class="mt-4">
                    <label class="form-label text-muted small">Notes</label>
                    <div class="border rounded p-3 bg-light">{!! nl2br(e($invoice->notes)) !!}</div>
                </div>
            @endif
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="{{ route('admin.invoices.index') }}" class="btn btn-outline-secondary">Back to Invoices</a>
            @if($canMoveToDispatch ?? false)
                <p class="mb-0 small text-muted">Click <strong>Move to dispatch</strong> above to create the order and save back orders; then they will appear on <a href="{{ route('admin.back_orders.index') }}">Back Orders</a>.</p>
            @endif
        </div>
    </div>
@endsection
