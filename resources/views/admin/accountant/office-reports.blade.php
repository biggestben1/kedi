@extends('layouts.admin')

@section('title', 'Office Reports')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Office Reports</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Office Reports</li>
            </ol>
            <p class="text-muted mb-0">
                Financial summary for each office in your organisation — Headquarters, Branch, Service Center, and Annex.
                @if(!empty($scopeLabel))
                    <br><span class="small">Scope: <strong>{{ $scopeLabel }}</strong></span>
                @endif
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.pharmacy.financial', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-success">Combined Financial Report</a>
            <a href="{{ route('admin.pharmacy.reports', ['from' => $from, 'to' => $to]) }}" class="btn btn-outline-primary">All Sales Reports</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ route('admin.accountant.office-reports') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">From</label>
                    <input type="date" name="from" class="form-control" value="{{ $from }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">To</label>
                    <input type="date" name="to" class="form-control" value="{{ $to }}">
                </div>
                <div class="col-md-6 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="{{ route('admin.accountant.office-reports') }}" class="btn btn-outline-secondary">Last 30 days</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">All offices — sales</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($totals->sales_total, 2) }}</div>
                    <div class="small text-muted">Invoices + shop + kits</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">All offices — wallet top-ups</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($totals->wallet_topup_total, 2) }}</div>
                    <div class="small text-muted">{{ number_format($totals->wallet_topup_count) }} top-up(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small">Paid invoices</div>
                    <div class="fs-4 fw-semibold">₦{{ number_format($totals->invoice_total, 2) }}</div>
                    <div class="small text-muted">{{ number_format($totals->invoice_count) }} invoice(s)</div>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3">
            <div class="card border-primary h-100">
                <div class="card-body">
                    <div class="text-muted small">All money in</div>
                    <div class="fs-4 fw-bold text-primary">₦{{ number_format($totals->all_money_in, 2) }}</div>
                    <div class="small text-muted">Sales + wallet top-ups</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Reports by office</h3>
            <span class="badge bg-primary">{{ $officeRows->count() }} office(s)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Office type</th>
                            <th>Office name</th>
                            <th class="text-end">Invoices</th>
                            <th class="text-end">Shop orders</th>
                            <th class="text-end">Kit purchases</th>
                            <th class="text-end">Wallet top-ups</th>
                            <th class="text-end">Sales total</th>
                            <th class="text-end">All money in</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($officeRows as $row)
                            @php
                                $typeBadge = match ($row->office?->role?->name) {
                                    'headquarters' => 'dark',
                                    'branch' => 'primary',
                                    'service_center' => 'info',
                                    'annex' => 'secondary',
                                    default => 'light',
                                };
                            @endphp
                            <tr>
                                <td><span class="badge bg-{{ $typeBadge }}">{{ $row->type }}</span></td>
                                <td>
                                    <div class="fw-semibold">{{ $row->name }}</div>
                                    @if($row->email)
                                        <div class="small text-muted">{{ $row->email }}</div>
                                    @endif
                                </td>
                                <td class="text-end">
                                    ₦{{ number_format($row->invoice_total, 2) }}
                                    <div class="small text-muted">{{ number_format($row->invoice_count) }}</div>
                                </td>
                                <td class="text-end">
                                    ₦{{ number_format($row->shop_total, 2) }}
                                    <div class="small text-muted">{{ number_format($row->shop_count) }}</div>
                                </td>
                                <td class="text-end">
                                    ₦{{ number_format($row->kit_total, 2) }}
                                    <div class="small text-muted">{{ number_format($row->kit_count) }}</div>
                                </td>
                                <td class="text-end">
                                    ₦{{ number_format($row->wallet_topup_total, 2) }}
                                    <div class="small text-muted">{{ number_format($row->wallet_topup_count) }}</div>
                                </td>
                                <td class="text-end fw-semibold">₦{{ number_format($row->sales_total, 2) }}</td>
                                <td class="text-end fw-semibold text-success">₦{{ number_format($row->all_money_in, 2) }}</td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-1 flex-wrap">
                                        <a href="{{ route('admin.pharmacy.financial', ['from' => $from, 'to' => $to, 'office_id' => $row->office_id]) }}"
                                           class="btn btn-sm btn-outline-success">Financial</a>
                                        <a href="{{ route('admin.pharmacy.reports', ['from' => $from, 'to' => $to, 'office_id' => $row->office_id]) }}"
                                           class="btn btn-sm btn-outline-primary">Sales</a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted p-4">
                                    No offices found in your organisation. Contact your administrator if this looks wrong.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($officeRows->isNotEmpty())
                        <tfoot>
                            <tr class="table-light">
                                <th colspan="2">All offices combined</th>
                                <th class="text-end">₦{{ number_format($totals->invoice_total, 2) }}</th>
                                <th class="text-end">₦{{ number_format($totals->shop_total, 2) }}</th>
                                <th class="text-end">₦{{ number_format($totals->kit_total, 2) }}</th>
                                <th class="text-end">₦{{ number_format($totals->wallet_topup_total, 2) }}</th>
                                <th class="text-end">₦{{ number_format($totals->sales_total, 2) }}</th>
                                <th class="text-end">₦{{ number_format($totals->all_money_in, 2) }}</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>
@endsection
