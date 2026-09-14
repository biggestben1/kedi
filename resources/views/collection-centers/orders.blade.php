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
                                <td>
                                    {{ $order->invoice_number ?: ('ORD-'.$order->id) }}
                                    <div class="mt-2 d-flex flex-column align-items-start gap-1">
                                        <a href="{{ route('collection-centers.invoice', $order) }}" class="btn btn-sm btn-outline-primary" target="_blank">View invoice</a>
                                        @if($order->payment_proof)
                                            <a href="{{ asset('storage/'.$order->payment_proof) }}" class="btn btn-sm btn-outline-secondary" target="_blank">View proof of payment</a>
                                        @else
                                            <span class="small text-muted">View proof of payment — not uploaded yet</span>
                                        @endif
                                    </div>
                                </td>
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
                                <td></td>
                            </tr>
                            <tr>
                                <td colspan="7" class="bg-light">
                                    @if($canCollect ?? false)
                                        <form method="POST" action="{{ route('collection-centers.collect', $order) }}" enctype="multipart/form-data" onsubmit="return confirm('Collect this order and remove it from branch stock?');">
                                            @csrf
                                            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#payment-{{ $order->id }}">Payment</button>
                                            <button class="btn btn-success ms-2">Collected</button>
                                            <div class="collapse mt-2" id="payment-{{ $order->id }}">
                                                @include('collection-centers.payment-details', ['order' => $order])
                                                <label class="form-label mb-1">Upload proof (optional)</label>
                                                <input type="file" name="payment_proof" class="form-control mb-2" accept="image/*,.pdf">
                                            </div>
                                        </form>
                                    @else
                                        <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#payment-{{ $order->id }}">Payment</button>
                                        <div class="collapse mt-2" id="payment-{{ $order->id }}">
                                            @include('collection-centers.payment-details', ['order' => $order])
                                            @if($order->payment_proof)
                                                <a href="{{ asset('storage/'.$order->payment_proof) }}" target="_blank">View proof of payment</a>
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
