@extends('layouts.admin')

@section('title', 'Financial Report')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Financial Report</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                @if(auth()->user()->role?->name === 'accountant')
                    <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.financial') }}">Financial Report</a></li>
                @else
                    <li class="breadcrumb-item"><a href="{{ route('admin.pharmacy.dashboard') }}">Dashboard</a></li>
                @endif
                <li class="breadcrumb-item active" aria-current="page">Financial Report</li>
            </ol>
            <p class="text-muted mb-0">
                Money paid in
                @if($isToday)
                    <strong>today</strong>
                @else
                    from <strong>{{ \Carbon\Carbon::parse($from)->format('M d, Y') }}</strong>
                    to <strong>{{ \Carbon\Carbon::parse($to)->format('M d, Y') }}</strong>
                @endif
                — invoices, shop orders, wallet top-ups, and kit purchases.
                @if(!empty($scopeLabel))
                    <br><span class="small">Scope: <strong>{{ $scopeLabel }}</strong></span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            @if(auth()->user()->role?->name === 'accountant')
                <a href="{{ route('admin.accountant.office-reports', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-secondary">Office Reports</a>
            @endif
            <a href="{{ route('admin.pharmacy.reports') }}" class="btn btn-outline-secondary">All Reports</a>
            <a href="{{ route('admin.pharmacy.financial', ['from' => now()->toDateString(), 'to' => now()->toDateString()]) }}" class="btn btn-primary">Today</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.pharmacy.financial') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                @if(!empty($offices) && $offices->isNotEmpty())
                <div class="col-md-3">
                    <label class="form-label">Office</label>
                    <select name="office_id" class="form-select">
                        <option value="">All offices</option>
                        @foreach($offices as $office)
                            <option value="{{ $office->id }}" {{ (int) ($officeId ?? 0) === (int) $office->id ? 'selected' : '' }}>
                                {{ \App\Support\OrgUserScope::orgUnitLabel($office) }} — {{ $office->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-{{ !empty($offices) && $offices->isNotEmpty() ? '3' : '6' }} d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.pharmacy.financial') }}" class="btn btn-outline-secondary">Reset to today</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Paid invoices</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($invoiceTotal, 2) }}</div>
                    <div class="small text-muted">{{ $paidInvoices->count() }} invoice(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Shop orders</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($shopTotal, 2) }}</div>
                    <div class="small text-muted">{{ $shopOrders->count() }} order(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Kit purchases</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($kitTotal, 2) }}</div>
                    <div class="small text-muted">{{ $kitPurchases->count() }} purchase(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Wallet top-ups</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($walletTopupTotal, 2) }}</div>
                    <div class="small text-muted">{{ $walletTopups->count() }} top-up(s)</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small">Total sales collected (invoices + shop + kits)</div>
                    <div class="display-6 fw-bold text-primary">₦{{ number_format($salesTotal, 2) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-success h-100">
                <div class="card-body">
                    <div class="text-muted small">All money in (sales + wallet top-ups)</div>
                    <div class="display-6 fw-bold text-success">₦{{ number_format($allMoneyIn, 2) }}</div>
                    <div class="small text-muted mt-1">Note: wallet top-ups fund later shop/invoice payments — they are shown separately from sales.</div>
                </div>
            </div>
        </div>
    </div>

    @if(isset($byLocation) && $byLocation->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">By location (HQ, Branch, Service Center, Annex)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Location</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($byLocation as $location => $amount)
                            <tr>
                                <td>{{ $location }}</td>
                                <td class="text-end">₦{{ number_format((float) $amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header">
            <h3 class="card-title mb-0">By payment method (invoices + shop)</h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($byPaymentMethod as $method => $amount)
                            <tr>
                                <td>{{ str_replace('_', ' ', ucfirst($method)) }}</td>
                                <td class="text-end">₦{{ number_format((float) $amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-center text-muted p-4">No payments in this period.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">All money paid in</h3>
            <span class="badge bg-primary">{{ $entries->count() }} record(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Source</th>
                            <th>Reference</th>
                            <th>Customer / User</th>
                            <th>Location</th>
                            <th>Method</th>
                            <th class="text-end">Amount</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $row)
                            <tr>
                                <td>{{ $row->when ? \Carbon\Carbon::parse($row->when)->format('M d, Y H:i') : '—' }}</td>
                                <td>
                                    @php
                                        $badge = match ($row->source) {
                                            'Invoice' => 'info',
                                            'Shop' => 'primary',
                                            'Collection' => 'dark',
                                            'Wallet top-up' => 'success',
                                            'Kit purchase' => 'warning',
                                            default => 'secondary',
                                        };
                                    @endphp
                                    <span class="badge bg-{{ $badge }}">{{ $row->source }}</span>
                                </td>
                                <td>{{ $row->reference }}</td>
                                <td>{{ $row->party }}</td>
                                <td>{{ $row->location ?? '—' }}</td>
                                <td>{{ $row->method && $row->method !== '—' ? str_replace('_', ' ', ucfirst($row->method)) : '—' }}</td>
                                <td class="text-end fw-semibold">₦{{ number_format($row->amount, 2) }}</td>
                                <td class="text-end">
                                    @if(!empty($row->url))
                                        <a href="{{ $row->url }}" class="btn btn-sm btn-outline-primary" target="_blank">View invoice</a>
                                    @endif
                                    @if(!empty($row->proof_url))
                                        <a href="{{ $row->proof_url }}" class="btn btn-sm btn-outline-secondary" target="_blank">View proof of payment</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted p-4">No money paid in for this date range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($entries->isNotEmpty())
                        <tfoot>
                            <tr>
                                <th colspan="6" class="text-end">Sales subtotal</th>
                                <th class="text-end">₦{{ number_format($salesTotal, 2) }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="6" class="text-end">Wallet top-ups</th>
                                <th class="text-end">₦{{ number_format($walletTopupTotal, 2) }}</th>
                                <th></th>
                            </tr>
                            <tr>
                                <th colspan="6" class="text-end">All money in</th>
                                <th class="text-end">₦{{ number_format($allMoneyIn, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
