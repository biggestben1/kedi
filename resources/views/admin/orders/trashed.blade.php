@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Trashed Orders</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.dispatch.orders.index') }}">Dispatcher Orders</a></li>
            <li class="breadcrumb-item active" aria-current="page">Trash</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Deleted Orders</h3>
                <div class="card-options">
                    <a href="{{ route('admin.dispatch.orders.index') }}" class="btn btn-primary btn-sm">Back to Orders</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Deleted At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr>
                                    <td>{{ $order->invoice_number ?? 'ORD-'.$order->id }}</td>
                                    <td>
                                        {{ $order->user?->name ?? '—' }}<br>
                                        <small class="text-muted">{{ $order->user?->email }}</small>
                                    </td>
                                    <td>₦{{ number_format($order->subtotal, 2) }}</td>
                                    <td><span class="badge bg-secondary">{{ strtoupper($order->status) }}</span></td>
                                    <td>{{ $order->deleted_at->format('M d, Y H:i') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.orders.restore', $order->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                        </form>
                                        <form action="{{ route('admin.orders.force-delete', $order->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this order?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Purge</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted p-4">Trash bin is empty.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($orders->hasPages())
                <div class="card-footer">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
