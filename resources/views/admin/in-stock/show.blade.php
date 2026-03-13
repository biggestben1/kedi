@extends('layouts.admin')

@section('title', 'Factory Invoice ' . $invoice->invoice_number)

@section('content')
    <div class="page-header">
        <h1 class="page-title">Factory Invoice</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.in-stock.index') }}">In Stock</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">{{ $invoice->invoice_number }}</h3>
            <div class="d-flex gap-2">
                @if(!$invoice->stock_added_at)
                <form action="{{ route('admin.in-stock.add-to-stock', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Add all quantities to product stock?');">
                    @csrf
                    <button type="submit" class="btn btn-success"><i class="fe fe-plus-circle me-1"></i>Add to Product Stock</button>
                </form>
                @else
                <span class="badge bg-success align-self-center">Stock added {{ $invoice->stock_added_at->format('M d, Y') }}</span>
                @endif
                <a href="{{ route('admin.in-stock.pdf', $invoice) }}" class="btn btn-primary" target="_blank"><i class="fe fe-file-text me-1"></i>Create PDF</a>
                <a href="{{ route('admin.in-stock.edit', $invoice) }}" class="btn btn-outline-primary">Edit</a>
                <form action="{{ route('admin.in-stock.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this factory invoice?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger">Delete</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <p class="mb-0 text-muted small">Factory</p>
                    <p class="mb-0">{{ $invoice->factory_name ?? '—' }}</p>
                </div>
                <div class="col-md-3">
                    <p class="mb-0 text-muted small">Invoice Date</p>
                    <p class="mb-0">{{ $invoice->invoice_date->format('M d, Y') }}</p>
                </div>
                @if($invoice->notes)
                <div class="col-md-6">
                    <p class="mb-0 text-muted small">Notes</p>
                    <p class="mb-0">{{ $invoice->notes }}</p>
                </div>
                @endif
            </div>

            <h5 class="mb-3">Products (status per product) – check items brought, save, then Create PDF</h5>
            <form action="{{ route('admin.in-stock.update-brought', $invoice) }}" method="POST" id="brought-form">
                @csrf
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th class="text-center" style="width:80px">Brought</th>
                                <th>Product</th>
                                <th class="text-end">Quantity</th>
                                <th>Status</th>
                                <th class="text-end">Cost Price</th>
                                <th class="text-end">Line Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                                <tr class="{{ $item->is_brought ? 'table-success' : '' }}">
                                    <td class="text-center">
                                        <input type="checkbox" name="brought[]" value="{{ $item->id }}" {{ $item->is_brought ? 'checked' : '' }} class="form-check-input" title="Item brought/received">
                                    </td>
                                    <td>{{ $item->product_name }} <small class="text-muted">({{ $item->item_code }})</small></td>
                                    <td class="text-end">{{ number_format($item->quantity) }}</td>
                                    <td>
                                        <span class="badge bg-{{ $item->status === 'backorder' ? 'secondary' : ($item->status === 'achievement' ? 'success' : 'info') }}">
                                            {{ $statusOptions[$item->status] ?? $item->status }}
                                        </span>
                                    </td>
                                    <td class="text-end">₦{{ number_format($item->cost_price, 2) }}</td>
                                    <td class="text-end">₦{{ number_format($item->line_total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button type="submit" class="btn btn-outline-secondary btn-sm mt-2"><i class="fe fe-save me-1"></i>Save Brought</button>
            </form>
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.in-stock.index') }}" class="btn btn-outline-secondary">← Back to In Stock</a>
        </div>
    </div>
@endsection
