@extends('layouts.admin')

@section('title', 'Coupons')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Coupons</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Coupons</li>
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
        <div class="card-header">
            <h3 class="card-title">Coupon Codes</h3>
            <div class="card-options">
                <a href="{{ route('admin.coupons.create') }}" class="btn btn-primary btn-sm">
                    <i class="fe fe-plus me-1"></i>Create Coupon
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Discount (%)</th>
                            <th>Expires at</th>
                            <th>Status</th>
                            <th>Used By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($coupons as $coupon)
                            <tr>
                                <td><strong>{{ $coupon->code }}</strong></td>
                                <td>{{ number_format($coupon->discount_percentage, 0) }}%</td>
                                <td>{{ $coupon->expires_at ? $coupon->expires_at->format('M d, Y H:i') : 'No expiry' }}</td>
                                <td>
                                    @if($coupon->used_count > 0)
                                        <span class="badge bg-secondary">Used</span>
                                    @elseif($coupon->isValid())
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-danger">Exp/Inact</span>
                                    @endif
                                </td>
                                <td>
                                    @foreach($coupon->orders as $order)
                                        <span class="badge bg-light text-dark mb-1" title="Order #{{ $order->invoice_number }}">{{ $order->user->name }}</span>
                                    @endforeach
                                    @if($coupon->orders->isEmpty())
                                        <span class="text-muted small">Not used yet</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this coupon?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted p-4">No coupons found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($coupons->hasPages())
            <div class="card-footer">
                {{ $coupons->links() }}
            </div>
        @endif
    </div>
@endsection
