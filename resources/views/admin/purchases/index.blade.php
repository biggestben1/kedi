@extends('layouts.admin')

@section('title', 'Purchases')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Purchases</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Purchases</li>
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Purchase Invoices</h3>
            <a href="{{ route('admin.purchases.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New Purchase Invoice</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Purchase Invoice</th>
                            <th>Supplier</th>
                            <th>Purchase Date</th>
                            <th>Payment Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($purchases as $p)
                            <tr>
                                <td>{{ $p->purchase_invoice ?: '#' . $p->id }}</td>
                                <td>{{ $p->supplier?->name ?? '—' }}</td>
                                <td>{{ $p->purchase_date->format('M d, Y') }}</td>
                                <td>
                                    @if($p->payment_status === 'paid')
                                        <span class="badge bg-success">Paid</span>
                                    @elseif($p->payment_status === 'partial')
                                        <span class="badge bg-warning">Partial</span>
                                    @else
                                        <span class="badge bg-secondary">Pending</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.purchases.edit', $p) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.purchases.destroy', $p) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this purchase?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No purchases. <a href="{{ route('admin.purchases.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($purchases->hasPages())
            <div class="card-footer">{{ $purchases->links() }}</div>
        @endif
    </div>
@endsection
