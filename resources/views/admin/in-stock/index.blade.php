@extends('layouts.admin')

@section('title', 'In Stock (Factory Invoices)')

@section('content')
    <div class="page-header">
        <h1 class="page-title">In Stock</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.products.index') }}">Products</a></li>
                <li class="breadcrumb-item active" aria-current="page">In Stock</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0">Factory Invoices</h3>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <select name="status" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                        <option value="">All statuses</option>
                        @foreach($statusOptions as $val => $label)
                            <option value="{{ $val }}" {{ ($statusFilter ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>
            <a href="{{ route('admin.in-stock.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New Factory Invoice</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice #</th>
                            <th>Factory</th>
                            <th>Invoice Date</th>
                            <th>Items</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $inv)
                            <tr>
                                <td>{{ $inv->invoice_number }}</td>
                                <td>{{ $inv->factory_name ?? '—' }}</td>
                                <td>{{ $inv->invoice_date->format('M d, Y') }}</td>
                                <td>{{ $inv->items->count() }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.in-stock.show', $inv) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('admin.in-stock.edit', $inv) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('admin.in-stock.destroy', $inv) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this factory invoice?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No factory invoices. <a href="{{ route('admin.in-stock.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($invoices->hasPages())
            <div class="card-footer">{{ $invoices->links() }}</div>
        @endif
    </div>
@endsection
