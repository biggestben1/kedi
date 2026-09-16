@extends('layouts.customer')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('order-groups.index') }}">Order Groups</a></li>
    <li class="breadcrumb-item active" aria-current="page">{{ $group->displayName() }}</li>
@endsection

@section('content')
@foreach (['success', 'message', 'error'] as $flash)
    @if(session($flash))
        <div class="alert alert-{{ $flash === 'error' ? 'danger' : 'success' }} alert-dismissible fade show">
            {{ session($flash) }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
@endforeach
@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
@endif

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
            <form method="POST" action="{{ route('order-groups.resume', $group) }}">@csrf<input type="hidden" name="go_shop" value="1"><button class="btn btn-primary"><i class="fe fe-play me-1"></i>Activate group</button></form>
        @endif
        @if($isActive)
            <a href="{{ route('shop') }}" class="btn btn-success"><i class="fe fe-shopping-bag me-1"></i>Go to shop</a>
            <form method="POST" action="{{ route('order-groups.end', $group) }}">@csrf<button class="btn btn-outline-warning">Pause group</button></form>
        @endif
    </div>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Orders in this group</h3></div>
            <div class="card-body">
                @if($group->orders->isEmpty())
                    <p class="text-muted mb-0">No orders yet. Add drafts below, or shop and use <strong>Add to Group</strong> while the session is active.</p>
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

        @if($group->isOpen() && ($availableDrafts ?? collect())->isNotEmpty())
        <div class="card mt-3">
            <div class="card-header"><h3 class="card-title mb-0">Add saved drafts to this group</h3></div>
            <div class="card-body">
                <p class="text-muted small mb-3">Drafts saved earlier (including ones from a cancelled group or another open group) can be moved here.</p>
                <form method="POST" action="{{ route('order-groups.add-drafts', $group) }}" id="add-drafts-form">
                    @csrf
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                            <tr>
                                <th style="width:36px;"><input type="checkbox" class="form-check-input" id="select-all-available-drafts" title="Select all"></th>
                                <th>Invoice / Tracking</th>
                                <th>KEDI / Customer</th>
                                <th>Items</th>
                                <th class="text-end">Total</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($availableDrafts as $draft)
                                <tr>
                                    <td>
                                        <input type="checkbox" class="form-check-input js-available-draft" name="order_ids[]" value="{{ $draft->id }}">
                                    </td>
                                    <td>
                                        <a href="{{ route('orders.show', $draft) }}">{{ $draft->invoice_number ?: $draft->tracking_number }}</a>
                                        <div class="small text-muted">{{ $draft->created_at->format('M j, Y H:i') }}</div>
                                        @if($draft->order_group_id)
                                            <span class="badge bg-warning text-dark">In another group</span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $draft->kd_id ?: '—' }}
                                        @if($draft->customer_name)<br><small class="text-muted">{{ $draft->customer_name }}</small>@endif
                                    </td>
                                    <td>{{ $draft->items->sum('quantity') }}</td>
                                    <td class="text-end">₦{{ number_format($draft->subtotal, 0) }}</td>
                                    <td class="text-end text-nowrap">
                                        <button type="button" class="btn btn-sm btn-primary js-add-one-draft" value="{{ $draft->id }}">
                                            <i class="fe fe-layers me-1"></i>Add
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" class="btn btn-primary" id="add-selected-drafts-btn" disabled>
                            <i class="fe fe-layers me-1"></i>Add selected drafts to group
                        </button>
                    </div>
                </form>
            </div>
        </div>
        @elseif($group->isOpen())
        <div class="card mt-3">
            <div class="card-body">
                <p class="mb-0 text-muted">No other drafts available to add. <a href="{{ route('orders.index', ['status' => 'draft']) }}">View My Drafts</a> or shop to create new ones.</p>
            </div>
        </div>
        @endif
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
                <p class="mb-3">Session is open. Add saved drafts on the left, or shop and add new orders, then pay everything at once.</p>
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

@push('scripts')
<script>
(function () {
    var form = document.getElementById('add-drafts-form');
    var selectAll = document.getElementById('select-all-available-drafts');
    var boxes = document.querySelectorAll('.js-available-draft');
    var btn = document.getElementById('add-selected-drafts-btn');
    function refresh() {
        var n = 0;
        boxes.forEach(function (b) { if (b.checked) n++; });
        if (btn) {
            btn.disabled = n < 1;
            btn.innerHTML = '<i class="fe fe-layers me-1"></i>Add selected drafts to group' + (n ? ' (' + n + ')' : '');
        }
        if (selectAll) selectAll.checked = boxes.length > 0 && n === boxes.length;
    }
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            boxes.forEach(function (b) { b.checked = selectAll.checked; });
            refresh();
        });
    }
    boxes.forEach(function (b) { b.addEventListener('change', refresh); });
    document.querySelectorAll('.js-add-one-draft').forEach(function (addBtn) {
        addBtn.addEventListener('click', function (e) {
            e.preventDefault();
            if (!form) return;
            boxes.forEach(function (b) { b.checked = String(b.value) === String(addBtn.value); });
            refresh();
            form.requestSubmit ? form.requestSubmit(btn) : form.submit();
        });
    });
    refresh();
})();
</script>
@endpush
