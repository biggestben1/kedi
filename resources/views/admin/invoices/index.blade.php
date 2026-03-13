@extends('layouts.admin')

@section('title', 'Invoices')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Invoices</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Invoices</li>
            </ol>
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
                <form method="GET" action="{{ route('admin.invoices.index') }}" class="d-flex gap-2">
                    <input type="search" name="q" class="form-control form-control-sm" placeholder="Search..." value="{{ $search }}" style="min-width: 200px;">
                    <select name="status" class="form-select form-select-sm" style="min-width: 150px;">
                        <option value="">All Status</option>
                        <option value="draft" {{ $statusFilter === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="sent" {{ $statusFilter === 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="paid" {{ $statusFilter === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="overdue" {{ $statusFilter === 'overdue' ? 'selected' : '' }}>Overdue</option>
                        <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-sm btn-outline-primary">Filter</button>
                    @if($search || $statusFilter)
                        <a href="{{ route('admin.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                                <td class="text-end">₦{{ number_format($invoice->total, 2) }}</td>
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
