@extends('layouts.admin')

@section('title', 'Invoices sent to '.$branch->name)

@section('content')
    <div class="page-header">
        <h1 class="page-title">Invoices sent to {{ $branch->name }}</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link {{ $collected ? '' : 'active' }}" href="{{ route('collection-centers.invoices') }}">Waiting for collection</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $collected ? 'active' : '' }}" href="{{ route('collection-centers.invoices', ['status' => 'collected']) }}">Collected</a>
        </li>
    </ul>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Sent by</th>
                            @if($collected)
                                <th>Collected</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($invoices as $invoice)
                            @php $order = $invoice->order; @endphp
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $invoice->invoice_number }}</div>
                                    <small class="text-muted">{{ $invoice->created_at->format('M d, Y H:i') }}</small>
                                    <div class="mt-2 d-flex flex-column align-items-start gap-1">
                                        <a href="{{ route('collection-centers.invoice', $order) }}" class="btn btn-sm btn-outline-primary" target="_blank">View invoice</a>
                                        @if($order->payment_proof)
                                            <a href="{{ route('collection-centers.proof.show', $order) }}" class="btn btn-sm btn-outline-secondary" target="_blank">View proof of payment</a>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $invoice->customer_name }}
                                    @if($invoice->customer_phone)
                                        <br><small class="text-muted">{{ $invoice->customer_phone }}</small>
                                    @endif
                                </td>
                                <td>
                                    @foreach($invoice->items as $item)
                                        <div>
                                            {{ $item->item_name }} × {{ $item->quantity }}
                                            @unless($item->product_id)
                                                <small class="text-warning">(no stock)</small>
                                            @endunless
                                        </div>
                                    @endforeach
                                    @if($invoice->notes)
                                        <small class="text-muted">{{ $invoice->notes }}</small>
                                    @endif
                                </td>
                                <td>₦{{ number_format($invoice->subtotal, 2) }}</td>
                                <td>{{ $invoice->user?->name }}</td>
                                @if($collected)
                                    <td>{{ $order->collected_at?->format('M d, Y H:i') }}</td>
                                @endif
                            </tr>
                            @unless($collected)
                                <tr>
                                    <td colspan="6" class="bg-light">
                                        <form method="POST" action="{{ route('collection-centers.collect', $order) }}" enctype="multipart/form-data" onsubmit="return confirm('Mark this invoice collected and remove the items from branch stock?');">
                                            @csrf
                                            <button type="button" class="btn btn-primary" data-bs-toggle="collapse" data-bs-target="#payment-{{ $order->id }}">Payment</button>
                                            <button class="btn btn-success ms-2">Collected</button>
                                            <div class="collapse mt-2" id="payment-{{ $order->id }}">
                                                <div class="border rounded p-2 mb-2 bg-white small">Customer pays here at collection: <strong>₦{{ number_format($invoice->subtotal, 2) }}</strong></div>
                                                <label class="form-label mb-1">Upload proof (optional)</label>
                                                <input type="file" name="payment_proof" class="form-control mb-2" accept="image/*,.pdf">
                                            </div>
                                        </form>

                                        @if($collectionBranches->isNotEmpty())
                                            <div class="border rounded p-3 mt-3 bg-white">
                                                <div class="fw-semibold mb-1">Can't fulfill here?</div>
                                                <p class="small text-muted mb-2">If there is a stock or other problem, move this invoice to another collection center.</p>
                                                <form method="POST" action="{{ route('collection-centers.move', $order) }}" class="row g-2 align-items-end" onsubmit="return confirm('Move this invoice to the selected collection center?');">
                                                    @csrf
                                                    <div class="col-md-8">
                                                        <label class="form-label mb-1" for="move-branch-{{ $order->id }}">Move to another collection center</label>
                                                        <select name="collection_branch_id" id="move-branch-{{ $order->id }}" class="form-select" required>
                                                            <option value="">— Select branch —</option>
                                                            @foreach($collectionBranches as $otherBranch)
                                                                <option value="{{ $otherBranch->id }}">
                                                                    {{ $otherBranch->name }}@if($otherBranch->phone) — {{ $otherBranch->phone }}@endif
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <button type="submit" class="btn btn-outline-warning w-100">
                                                            <i class="fe fe-navigation me-1"></i>Move invoice
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endunless
                        @empty
                            <tr><td colspan="6" class="text-muted p-4">{{ $collected ? 'No collected invoices yet.' : 'No invoices waiting for collection.' }}</td></tr>
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
