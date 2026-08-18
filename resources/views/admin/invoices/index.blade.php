@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
    <div class="page-header">
        <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
            <div>
                <h1 class="page-title mb-1">Invoices</h1>
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Invoices</li>
                </ol>
            </div>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <a href="{{ route('admin.invoices.index', array_merge(request()->only(['status','q']), ['period' => 'today'])) }}" class="btn btn-lg {{ $periodFilter === 'today' ? 'btn-primary' : 'btn-outline-primary' }} d-inline-flex align-items-center">
                    Today
                    <span class="badge bg-secondary ms-2">{{ (int) ($periodCounts['today'] ?? 0) }}</span>
                </a>
                <a href="{{ route('admin.invoices.index', array_merge(request()->only(['status','q']), ['period' => 'month'])) }}" class="btn btn-lg {{ $periodFilter === 'month' ? 'btn-primary' : 'btn-outline-primary' }} d-inline-flex align-items-center">
                    Month
                    <span class="badge bg-secondary ms-2">{{ (int) ($periodCounts['month'] ?? 0) }}</span>
                </a>
                <a href="{{ route('admin.invoices.index', array_merge(request()->only(['status','q']), ['period' => 'year'])) }}" class="btn btn-lg {{ $periodFilter === 'year' ? 'btn-primary' : 'btn-outline-primary' }} d-inline-flex align-items-center">
                    Year
                    <span class="badge bg-secondary ms-2">{{ (int) ($periodCounts['year'] ?? 0) }}</span>
                </a>
                <a href="{{ route('admin.invoices.index', request()->except(['page','period'])) }}" class="btn btn-lg btn-outline-secondary d-inline-flex align-items-center">
                    Clear period
                </a>
                <a href="{{ route('admin.invoices.index', ['status' => 'draft']) }}" class="btn btn-lg btn-outline-primary d-inline-flex align-items-center">
                    Draft
                    <span class="badge bg-secondary ms-2">{{ (int) ($statusCounts['draft'] ?? 0) }}</span>
                </a>
                <a href="{{ route('admin.invoices.index', ['status' => 'sent']) }}" class="btn btn-lg btn-outline-primary d-inline-flex align-items-center">
                    Sent
                    <span class="badge bg-warning text-dark ms-2">{{ (int) ($statusCounts['sent'] ?? 0) }}</span>
                </a>
                <a href="{{ route('admin.invoices.index', ['status' => 'paid']) }}" class="btn btn-lg btn-outline-primary d-inline-flex align-items-center">
                    Paid
                    <span class="badge bg-success ms-2">{{ (int) ($statusCounts['paid'] ?? 0) }}</span>
                </a>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            @if(session('created_invoice_id'))
                <a href="{{ route('admin.invoices.pdf', session('created_invoice_id')) }}" class="alert-link ms-2" target="_blank" rel="noopener">Download PDF</a>
            @endif
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Invoices</h3>
            <div class="d-flex gap-2 flex-wrap">
                <form method="GET" action="{{ route('admin.invoices.index') }}" class="d-flex gap-2 flex-wrap align-items-center">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Search..." value="{{ $search }}" style="min-width: 200px;">
                    <select name="status" class="form-select form-select-sm" style="min-width: 150px;">
                        <option value="">All Status</option>
                        <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ $statusFilter === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <input type="date" name="from_date" class="form-control form-control-sm" value="{{ request('from_date') }}" style="max-width: 170px;">
                    <input type="date" name="to_date" class="form-control form-control-sm" value="{{ request('to_date') }}" style="max-width: 170px;">
                    @if($periodFilter)
                        <input type="hidden" name="period" value="{{ $periodFilter }}">
                    @endif
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    <a href="{{ route('admin.invoices.index', request()->except(['page','from_date','to_date'])) }}" class="btn btn-sm btn-outline-secondary">Clear range</a>
                    @if($search || $statusFilter || $periodFilter)
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Clear all</a>
                    @endif
                </form>
                <a href="{{ route('admin.invoices.create') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus me-1"></i>New Invoice</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            <tr>
                                <td><strong>{{ $invoice->invoice_number }}</strong></td>
                                <td>
                                    {{ $invoice->customer_name ?? $invoice->user?->name ?? '—' }}
                                    @if($invoice->customer_email)
                                        <br><small class="text-muted">{{ $invoice->customer_email }}</small>
                                    @endif
                                </td>
                                <td>{{ $invoice->invoice_date->format('M d, Y') }}</td>
                                <td>{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</td>
                                <td class="text-end">₦{{ preg_match('/\.00$/', number_format($invoice->total, 2)) ? number_format($invoice->total, 0) : number_format($invoice->total, 2) }}</td>
                                <td>
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
                                </td>
                                <td class="text-end">
                                    @if($invoice->user_id && !$invoice->order)
                                        @if(in_array($invoice->id, $invoiceIdsRequireApproval ?? []))
                                            <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-success"><i class="fe fe-check me-1"></i>Approve (opens invoice)</a>
                                        @else
                                            <form action="{{ route('admin.invoices.move-to-dispatch', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Move this invoice to dispatch? An order will be created for the dispatcher to process.');">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success"><i class="fe fe-truck me-1"></i>Move to dispatch</button>
                                            </form>
                                        @endif
                                    @elseif($invoice->order)
                                    <a href="{{ route('admin.dispatch.orders.show', $invoice->order) }}" class="btn btn-sm btn-outline-success"><i class="fe fe-truck me-1"></i>In dispatch</a>
                                    @endif
                                    <a href="{{ route('admin.invoices.show', $invoice) }}" class="btn btn-sm btn-outline-info" title="View"><i class="fe fe-eye me-1"></i>View</a>
                                    <a href="{{ route('admin.invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" title="Download PDF"><i class="fe fe-file-text me-1"></i>PDF</a>
                                    <a href="{{ route('admin.invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this invoice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted p-4">No invoices. <a href="{{ route('admin.invoices.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection
