<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Invoice {{ $invoice->invoice_number }} – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        .app-header .logo-horizontal { display: none !important; }
        .app-header .logo-horizontal img { max-height: 52px; max-width: 200px; width: auto; height: auto; object-fit: contain; display: block !important; visibility: visible !important; }
        .app-sidebar .side-header .header-brand-img { max-height: 58px; max-width: 100%; display: block !important; visibility: visible !important; background-color: #fff !important; }
    </style>
</head>
<body class="app sidebar-mini ltr">
<div class="page">
    <div class="page-main">
        <div class="app-header header sticky">
            <div class="container-fluid main-container">
                <div class="d-flex">
                    <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                    <a class="logo-horizontal" href="{{ route('shop') }}">
                        <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                    </a>
                    <div class="main-header-center ms-3 d-none d-lg-block">
                        <a href="{{ route('shop') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                    </div>
                    <div class="d-flex order-lg-2 ms-auto header-right-icons">
                        <div class="navbar navbar-collapse responsive-navbar p-0">
                            <div class="d-flex order-lg-2">
                                <a class="nav-link icon text-center" href="{{ route('shop') }}">
                                    <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge">{{ $cartCount ?? 0 }}</span>
                                </a>
                                <div class="dropdown d-flex profile-1">
                                    <a href="javascript:void(0)" data-bs-toggle="dropdown" class="nav-link leading-none d-flex">
                                        <span class="avatar profile-user brround cover-image bg-primary text-white d-flex align-items-center justify-content-center">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                        <div class="drop-heading">
                                            <div class="text-center">
                                                <h5 class="text-dark mb-0 fs-14 fw-semibold">{{ auth()->user()->name }}</h5>
                                                <small class="text-muted">{{ auth()->user()->email }}</small>
                                            </div>
                                        </div>
                                        <div class="dropdown-divider m-0"></div>
                                        <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                        <a class="dropdown-item" href="{{ route('orders.index') }}"><i class="dropdown-icon fe fe-package"></i> My Orders</a>
                                        <a class="dropdown-item" href="{{ route('invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> My Invoices</a>
                                        <a class="dropdown-item" href="{{ route('wallet.index') }}"><i class="dropdown-icon fe fe-dollar-sign"></i> Wallet</a>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item w-100 text-start border-0 bg-transparent"><i class="dropdown-icon fe fe-log-out"></i> Sign out</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky">
            <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
            <div class="app-sidebar">
                <div class="side-header">
                    <a class="header-brand1" href="{{ url('/') }}">
                        <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                    </a>
                </div>
                <div class="main-sidemenu">
                    <ul class="side-menu">
                        <li class="sub-category"><h3>Main</h3></li>
                        <li class="slide">
                                <a class="side-menu__item" href="{{ route('shop') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
                        </li>
                        <li class="slide">
                            <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
                        </li>
                        <li class="slide">
                            <a class="side-menu__item" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
                        </li>
                        <li class="slide">
                            <a class="side-menu__item active" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                        </li>
                        <li class="sub-category"><h3>Account</h3></li>
                        <li class="slide">
                            <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                @csrf
                                <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content app-content mt-0">
            <div class="side-app">
                <div class="main-container container-fluid">
                    <div class="page-header">
                        <h1 class="page-title">Invoice {{ $invoice->invoice_number }}</h1>
                        <div>
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                <li class="breadcrumb-item"><a href="{{ route('invoices.index') }}">My Invoices</a></li>
                                <li class="breadcrumb-item active" aria-current="page">{{ $invoice->invoice_number }}</li>
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
                        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h3 class="card-title mb-0">Invoice Details</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-sm btn-outline-warning"><i class="fe fe-edit-2 me-1"></i>Edit</a>
                                <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener"><i class="fe fe-download me-1"></i>PDF</a>
                                <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this invoice? This cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fe fe-trash-2 me-1"></i>Delete</button>
                                </form>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <label class="form-label text-muted small">Invoice Date</label>
                                    <p class="mb-0">{{ $invoice->invoice_date?->format('M d, Y') ?? '—' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small">Due Date</label>
                                    <p class="mb-0">{{ $invoice->due_date ? $invoice->due_date->format('M d, Y') : '—' }}</p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small">Status</label>
                                    <p class="mb-0">
                                        @if($invoice->status === 'draft')
                                            <span class="badge bg-secondary">Draft</span>
                                        @elseif($invoice->status === 'sent')
                                            <span class="badge bg-info">Sent</span>
                                        @elseif($invoice->status === 'paid')
                                            <span class="badge bg-success">Paid</span>
                                        @elseif($invoice->status === 'overdue')
                                            <span class="badge bg-danger">Overdue</span>
                                        @else
                                            <span class="badge bg-warning text-dark">Cancelled</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label text-muted small">Total</label>
                                    <p class="mb-0 fw-semibold">₦{{ preg_replace('/\.00$/', '', number_format($invoice->total ?? 0, 2)) }}</p>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label text-muted small">Bill To</label>
                                <div class="border rounded p-3 bg-light">
                                    @if($invoice->customer_name)<strong>{{ $invoice->customer_name }}</strong><br>@endif
                                    @if($invoice->sc_referral_code)
                                        <strong>Service Center Referral Code:</strong>
                                        <a href="javascript:void(0)" class="sc-referral-link" data-sc-code="{{ $invoice->sc_referral_code }}">{{ $invoice->sc_referral_code }}</a><br>
                                    @endif
                                    @if($invoice->customer_email){{ $invoice->customer_email }}<br>@endif
                                    @if($invoice->customer_phone){{ $invoice->customer_phone }}<br>@endif
                                    @if($invoice->customer_address){!! nl2br(e($invoice->customer_address)) !!}@endif
                                    @if(!$invoice->customer_name && !$invoice->sc_referral_code && !$invoice->customer_email && !$invoice->customer_phone && !$invoice->customer_address)
                                        <span class="text-muted">—</span>
                                    @endif
                                </div>
                            </div>

                            <label class="form-label text-muted small">Items</label>
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th>Unit</th>
                                        <th class="text-end">Unit Price</th>
                                        <th class="text-end">Line Total</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($invoice->items as $index => $item)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>
                                                {{ $item->item_name }}
                                                @if($item->description)
                                                    <br><small class="text-muted">{{ $item->description }}</small>
                                                @endif
                                            </td>
                                            <td class="text-end">{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') ?: '0' }}</td>
                                            <td>{{ $item->unit ?? '—' }}</td>
                                            <td class="text-end">₦{{ preg_replace('/\.00$/', '', number_format($item->unit_price, 2)) }}</td>
                                            <td class="text-end">₦{{ preg_replace('/\.00$/', '', number_format($item->line_total, 2)) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="row justify-content-end mt-3">
                                <div class="col-md-4">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td class="border-0 text-muted">Subtotal</td>
                                            <td class="border-0 text-end">₦{{ preg_replace('/\.00$/', '', number_format($invoice->subtotal ?? 0, 2)) }}</td>
                                        </tr>
                                        @if((float) ($invoice->tax ?? 0) > 0)
                                            <tr>
                                                <td class="border-0 text-muted">Tax</td>
                                                <td class="border-0 text-end">₦{{ preg_replace('/\.00$/', '', number_format($invoice->tax, 2)) }}</td>
                                            </tr>
                                        @endif
                                        @if((float) ($invoice->discount ?? 0) > 0)
                                            <tr>
                                                <td class="border-0 text-muted">Discount</td>
                                                <td class="border-0 text-end">-₦{{ preg_replace('/\.00$/', '', number_format($invoice->discount, 2)) }}</td>
                                            </tr>
                                        @endif
                                        @if((float) ($invoice->coupon_discount_amount ?? 0) > 0)
                                            <tr>
                                                <td class="border-0 text-muted">Coupon Discount</td>
                                                <td class="border-0 text-end">-₦{{ preg_replace('/\.00$/', '', number_format($invoice->coupon_discount_amount, 2)) }}</td>
                                            </tr>
                                        @endif
                                        <tr>
                                            <td class="border-0 fw-bold">Total</td>
                                            <td class="border-0 text-end fw-bold">₦{{ preg_replace('/\.00$/', '', number_format($invoice->total ?? 0, 2)) }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            @php
                                $breakdown = is_array($invoice->payment_breakdown) ? $invoice->payment_breakdown : [];
                                $splitLabels = [
                                    'wallet' => 'Wallet',
                                    'kd_credit' => 'Kedi Credit',
                                    'cash' => 'Cash',
                                    'transfer' => 'Bank Transfer',
                                    'cheque' => 'Cheque',
                                    'pos' => 'POS',
                                    'bank' => 'Bank',
                                    'dpbv' => 'DPBV',
                                ];
                                $splitRows = [];
                                foreach ($splitLabels as $key => $label) {
                                    $amount = (float) ($breakdown[$key] ?? 0);
                                    if ($amount > 0) {
                                        $splitRows[$label] = $amount;
                                    }
                                }
                                // Fallback: POS amount stored separately when not in breakdown
                                if ($splitRows === [] && (float) ($invoice->pos_amount_paid ?? 0) > 0) {
                                    $splitRows['POS'] = (float) $invoice->pos_amount_paid;
                                }
                                $paymentMethodLabel = $invoice->payment_method
                                    ? str_replace('_', ' ', ucfirst($invoice->payment_method))
                                    : null;
                            @endphp

                            @if($paymentMethodLabel || $splitRows !== [])
                                <div class="mt-4">
                                    <label class="form-label text-muted small">Payment</label>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <div>
                                                <span class="text-muted">Method:</span>
                                                <strong class="ms-1">{{ $paymentMethodLabel ?? '—' }}</strong>
                                                @if($invoice->payment_method === 'split' || count($splitRows) > 1)
                                                    <span class="badge bg-info ms-2">Split</span>
                                                @endif
                                            </div>
                                            <div>
                                                <span class="text-muted">Invoice total:</span>
                                                <strong class="ms-1">₦{{ preg_replace('/\.00$/', '', number_format($invoice->total ?? 0, 2)) }}</strong>
                                            </div>
                                        </div>

                                        @if($splitRows !== [])
                                            <div class="table-responsive">
                                                <table class="table table-sm table-bordered mb-0 bg-white">
                                                    <thead>
                                                        <tr>
                                                            <th>Paid with</th>
                                                            <th class="text-end">Amount</th>
                                                            <th class="text-end" style="width:100px;">Share</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($splitRows as $label => $amount)
                                                            @php
                                                                $share = ((float) $invoice->total > 0)
                                                                    ? round(($amount / (float) $invoice->total) * 100, 1)
                                                                    : 0;
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $label }}</td>
                                                                <td class="text-end">₦{{ preg_replace('/\.00$/', '', number_format($amount, 2)) }}</td>
                                                                <td class="text-end text-muted">{{ $share }}%</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                    <tfoot>
                                                        <tr>
                                                            <th>Split total</th>
                                                            <th class="text-end">₦{{ preg_replace('/\.00$/', '', number_format(array_sum($splitRows), 2)) }}</th>
                                                            <th></th>
                                                        </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        @elseif($paymentMethodLabel)
                                            <p class="mb-0 small text-muted">Full amount paid via {{ $paymentMethodLabel }}.</p>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @if($invoice->notes)
                                <div class="mt-4">
                                    <label class="form-label text-muted small">Notes</label>
                                    <div class="border rounded p-3 bg-light">{!! nl2br(e($invoice->notes)) !!}</div>
                                </div>
                            @endif
                        </div>
                        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <a href="{{ route('invoices.index') }}" class="btn btn-outline-secondary">Back to My Invoices</a>
                            <a href="{{ route('invoices.create') }}" class="btn btn-success"><i class="fe fe-plus me-1"></i>Create New</a>
                        </div>
                    </div>

                    <div class="modal fade" id="scBalancesModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Service Center Wallet & DPBV</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div id="scBalancesStatus" class="small text-muted mb-2"></div>
                                    <div class="border rounded p-3 bg-light">
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Service Center</span>
                                            <strong id="scBalancesName">—</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span class="text-muted">Wallet</span>
                                            <strong>₦<span id="scBalancesWallet">0.00</span></strong>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span class="text-muted">DPBV</span>
                                            <strong><span id="scBalancesDpbv">0.00</span> DPBV</strong>
                                        </div>
                                        <div class="d-flex justify-content-between mt-2">
                                            <span class="text-muted">DPBV (₦)</span>
                                            <strong>₦<span id="scBalancesDpbvNaira">0.00</span></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="container">
        <div class="row align-items-center flex-row-reverse">
            <div class="col-md-12 col-sm-12 text-center">
                Copyright © <span id="year"></span> <a href="{{ url('/') }}">KEDI</a>. All rights reserved.
            </div>
        </div>
    </div>
    @include('partials.cloud-footer')
</footer>
<a href="#top" id="back-to-top"><i class="fa fa-angle-up"></i></a>

<script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('sash/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
<script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.js') }}"></script>
<script src="{{ asset('sash/assets/plugins/sidemenu/sidemenu.js') }}"></script>
<script src="{{ asset('sash/assets/plugins/sidebar/sidebar.js') }}"></script>
<script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
<script src="{{ asset('sash/assets/js/sticky.js') }}"></script>
<script src="{{ asset('sash/assets/js/custom.js') }}"></script>
<script>document.getElementById('year').textContent = new Date().getFullYear();</script>
<script>
    (function () {
        var links = document.querySelectorAll('.sc-referral-link');
        if (!links.length) return;

        var csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        var modalEl = document.getElementById('scBalancesModal');
        if (!modalEl) return;
        var modal = new bootstrap.Modal(modalEl);

        function fmt(num, digits) {
            var n = parseFloat(num || 0);
            return n.toFixed(digits).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        async function fetchBalances(code) {
            document.getElementById('scBalancesStatus').textContent = 'Loading...';
            document.getElementById('scBalancesName').textContent = '—';
            document.getElementById('scBalancesWallet').textContent = '0.00';
            document.getElementById('scBalancesDpbv').textContent = '0.00';
            document.getElementById('scBalancesDpbvNaira').textContent = '0.00';

            try {
                var res = await fetch('{{ route("service-center.balances") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ code: code })
                });
                var data = await res.json();
                if (!data || !data.valid) {
                    document.getElementById('scBalancesStatus').textContent = data?.message || 'Unable to load balances.';
                    return;
                }
                document.getElementById('scBalancesStatus').textContent = '';
                document.getElementById('scBalancesName').textContent = data.name || code;
                document.getElementById('scBalancesWallet').textContent = fmt(data.wallet_balance, 2);
                document.getElementById('scBalancesDpbv').textContent = fmt(data.dpbv, 2);
                document.getElementById('scBalancesDpbvNaira').textContent = fmt(data.dpbv_naira, 2);
            } catch (e) {
                document.getElementById('scBalancesStatus').textContent = 'Network error.';
            }
        }

        links.forEach(function (a) {
            a.addEventListener('click', function () {
                var code = (this.getAttribute('data-sc-code') || '').trim();
                if (!code) return;
                modal.show();
                fetchBalances(code);
            });
        });
    })();
</script>
@include('partials.pwa-scripts')
</body>
</html>

