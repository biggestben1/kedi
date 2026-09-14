@extends('layouts.admin')

@section('title', $title)

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ $title }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            @if(!($canCollect ?? false))
                                <th>Branch</th>
                                <th>Collected</th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($orders as $order)
                            <tr>
                                <td>{{ $order->invoice_number ?: ('ORD-'.$order->id) }}</td>
                                <td>
                                    {{ $order->customer_name ?: $order->user?->name }}<br>
                                    <small class="text-muted">{{ $order->kd_id }}</small>
                                </td>
                                <td>
                                    @foreach($order->items as $item)
                                        <div>{{ $item->product_name }} × {{ $item->quantity }}</div>
                                    @endforeach
                                </td>
                                <td>₦{{ number_format($order->subtotal, 0) }}</td>
                                @if(!($canCollect ?? false))
                                    <td>{{ $order->collectionBranch?->name ?: '—' }}</td>
                                    <td>{{ $order->collected_at?->format('M d, Y H:i') }}</td>
                                @endif
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="collapse" data-bs-target="#payment-{{ $order->id }}">Payment</button>
                                    @if($canCollect ?? false)
                                        <form method="POST" action="{{ route('collection-centers.collect', $order) }}" enctype="multipart/form-data" onsubmit="return confirm('Collect this order and remove it from branch stock?');">
                                            @csrf
                                            <div class="collapse text-start mt-2" id="payment-{{ $order->id }}">
                                                @include('collection-centers.payment-details', ['order' => $order])
                                                <label class="form-label small mb-1">Upload proof (optional)</label>
                                                <input type="file" name="payment_proof" class="form-control form-control-sm mb-2" accept="image/*,.pdf">
                                            </div>
                                            <button class="btn btn-sm btn-success">Collected</button>
                                        </form>
                                    @else
                                        <div class="collapse text-start mt-2" id="payment-{{ $order->id }}">
                                            @include('collection-centers.payment-details', ['order' => $order])
                                            @if($order->payment_proof)
                                                <a href="{{ asset('storage/'.$order->payment_proof) }}" target="_blank" class="small">View proof</a>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-muted p-4">No orders.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($orders->hasPages())
            <div class="card-footer">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
