@extends('layouts.customer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('order-groups.index') }}">Order Groups</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $group->displayName() }}</li>
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
        <p class="text-muted mb-0">
            Status: <span class="badge bg-{{ $group->status === 'open' ? 'info' : ($group->status === 'paid' ? 'success' : 'secondary') }}">{{ ucfirst($group->status) }}</span>
            @if($isActive)<span class="badge bg-success ms-1">Session active</span>@endif
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('order-groups.index') }}" class="btn btn-outline-secondary">All groups</a>
        <a href="{{ route('order-groups.edit', $group) }}" class="btn btn-outline-secondary">Edit</a>
        @if($group->isOpen() && $drafts->isNotEmpty())
            <a href="{{ route('order-groups.pay-form', $group) }}" class="btn btn-success"><i class="fe fe-credit-card me-1"></i>Pay all (₦{{ number_format($draftTotal, 0) }})</a>
        @endif
        <a href="{{ route('shop') }}" class="btn btn-outline-primary">Continue shopping</a>
        @if($group->isOpen() && !$isActive)
            <form method="POST" action="{{ route('order-groups.resume', $group) }}">@csrf<button class="btn btn-primary"><i class="fe fe-play me-1"></i>Reactivate session</button></form>
        @endif
        @if($isActive)
            <a href="{{ route('shop') }}" class="btn btn-success"><i class="fe fe-shopping-bag me-1"></i>Go to shop</a>
            <form method="POST" action="{{ route('order-groups.end', $group) }}">@csrf<button class="btn btn-outline-warning">Pause session</button></form>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Orders in this group</h3></div>
            <div class="card-body">
                @if($group->orders->isEmpty())
                    <p class="text-muted mb-0">No orders yet. Shop and use <strong>Add to Group</strong> while the session is active.</p>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead>
                            <tr>
                                <th>Invoice</th>
                                <th>KEDI / Customer</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th class="text-end">Total</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($group->orders as $order)
                                <tr>
                                    <td><a href="{{ route('orders.show', $order) }}">{{ $order->invoice_number }}</a></td>
                                    <td>
                                        {{ $order->kd_id ?: '—' }}
                                        @if($order->customer_name)<br><small class="text-muted">{{ $order->customer_name }}</small>@endif
                                    </td>
                                    <td>{{ $order->items->sum('quantity') }}</td>
                                    <td><span class="badge bg-secondary">{{ $order->status }}</span></td>
                                    <td class="text-end">₦{{ number_format($order->subtotal, 0) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot>
                            <tr>
                                <th colspan="4">Draft total (unpaid)</th>
                                <th class="text-end">₦{{ number_format($draftTotal, 0) }}</th>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        @if($group->isOpen() && $drafts->isNotEmpty())
        <div class="card" id="pay">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Pay for whole group</h3>
                <a href="{{ route('order-groups.pay-form', $group) }}" class="btn btn-sm btn-success">Open pay page</a>
            </div>
            <div class="card-body">
                <div class="p-3 bg-primary text-white rounded mb-3 text-center">
                    <div class="opacity-75">Group total</div>
                    <h2 class="mb-0">₦{{ number_format($draftTotal, 0) }}</h2>
                    <small>{{ $drafts->count() }} unpaid order(s)</small>
                </div>
                <a href="{{ route('order-groups.pay-form', $group) }}" class="btn btn-success btn-lg w-100">
                    <i class="fe fe-credit-card me-1"></i>Pay all ₦{{ number_format($draftTotal, 0) }}
                </a>
                <p class="small text-muted mt-3 mb-0">Wallet: ₦{{ number_format($walletBalance, 2) }} · DPBV: ₦{{ number_format($dpbvNairaEquivalent, 2) }} · KD Credit: ₦{{ number_format($kdCreditBalance, 2) }}</p>
                <form method="POST" action="{{ route('order-groups.cancel', $group) }}" class="mt-3" onsubmit="return confirm('Cancel this group session? Drafts stay in My Drafts.');">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100">Cancel group</button>
                </form>
            </div>
        </div>
        @elseif($group->isOpen())
        <div class="card">
            <div class="card-body">
                <p class="mb-3">Session is open. Add orders from the shop, then come back here to pay everything at once.</p>
                <a href="{{ route('shop') }}" class="btn btn-primary w-100 mb-2">Go to shop</a>
                <form method="POST" action="{{ route('order-groups.cancel', $group) }}" onsubmit="return confirm('Cancel this empty group?');">
                    @csrf
                    <button class="btn btn-outline-danger w-100">Cancel group</button>
                </form>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
