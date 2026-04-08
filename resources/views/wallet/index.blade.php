<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Wallet – {{ config('app.name') }}</title>
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
    <div id="global-loader">
        <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>

    <div class="page">
        <div class="page-main">
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex">
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                        <a class="logo-horizontal" href="{{ url('/') }}">
                            <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                        </a>
                        <div class="main-header-center ms-3 d-none d-lg-block">
                            <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                        </div>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-4" aria-controls="navbarSupportedContent-4" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-4">
                                    <div class="d-flex order-lg-2">
                                        <a class="nav-link icon text-center" href="{{ url('/') }}">
                                            <i class="fe fe-shopping-cart"></i><span class="badge bg-secondary header-badge">{{ $cartCount }}</span>
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
                                                <a class="dropdown-item" href="{{ route('dpbv.index') }}"><i class="dropdown-icon fe fe-award"></i> My DPBV</a>
                                                <a class="dropdown-item" href="{{ route('promo.index') }}"><i class="dropdown-icon fe fe-gift"></i> My Promo</a>
                                                <a class="dropdown-item" href="{{ route('bonus.index') }}"><i class="dropdown-icon fe fe-trending-up"></i> My Bonus</a>
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                                                @php
                                                    $walletRole = auth()->user()->role?->name;
                                                    $walletAdminLabel = match($walletRole) {
                                                        'reseller' => 'Reseller',
                                                        'accountant' => 'Accountant Panel',
                                                        'dispatch' => 'Dispatch Panel',
                                                        'headquarters' => 'Admin Dashboard',
                                                        'branch' => 'Branch Admin',
                                                        'service_center' => 'Service Center Admin',
                                                        default => 'Admin',
                                                    };
                                                @endphp
                                                <a class="dropdown-item" href="{{ $walletRole === 'headquarters' ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ $walletAdminLabel }}</a>
                                                @endif
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
                        <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"/></svg></div>
                        <ul class="side-menu">
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ url('/') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('checkout.show') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Checkout</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dpbv.index') }}"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">My DPBV</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                            @php
                                $walletSideRole = auth()->user()->role?->name;
                                $walletSideAdminLabel = match($walletSideRole) {
                                    'reseller' => 'Reseller',
                                    'accountant' => 'Accountant Panel',
                                    'dispatch' => 'Dispatch Panel',
                                    'headquarters' => 'Admin Dashboard',
                                    'branch' => 'Branch Admin',
                                    'service_center' => 'Service Center Admin',
                                    'annex' => 'Annex Admin',
                                    default => 'Admin',
                                };
                            @endphp
                            <li class="slide">
                                <a class="side-menu__item" href="{{ in_array($walletSideRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $walletSideAdminLabel }}</span></a>
                            </li>
                            @endif
                            <li class="sub-category"><h3>Account</h3></li>
                            <li class="slide">
                                <form method="POST" action="{{ route('logout') }}" class="d-inline">
                                    @csrf
                                    <button type="submit" class="side-menu__item border-0 bg-transparent w-100 text-start d-flex align-items-center"><i class="side-menu__icon fe fe-log-out"></i><span class="side-menu__label">Logout</span></button>
                                </form>
                            </li>
                        </ul>
                        <div class="slide-right" id="slide-right"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M10.707 17.707 16.414 12l-5.707-5.707-1.414 1.414L13.586 12l-4.293 4.293z"/></svg></div>
                    </div>
                </div>
            </div>

            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        <div class="page-header">
                            <h1 class="page-title">My Wallet</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Wallet</li>
                                </ol>
                            </div>
                        </div>

                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif

                        @if($banks->isNotEmpty())
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Bank Accounts</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        <p class="text-muted small px-4 pt-3 mb-0">Pay into any of these accounts when topping up your wallet. Use your name or order reference as payment reference.</p>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Bank Name</th>
                                                        <th>Account Name</th>
                                                        <th>Account Number</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($banks as $bank)
                                                    <tr>
                                                        <td>{{ $bank->name }}</td>
                                                        <td>{{ $bank->account_name ?? '—' }}</td>
                                                        <td>{{ $bank->account_number ?? '—' }}</td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="row">
                            <div class="col-lg-4 col-md-6">
                                <div class="card overflow-hidden">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-lg brround bg-primary-transparent me-3">
                                                <i class="fe fe-wallet fs-30 text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 text-muted">Available Balance</h6>
                                                <h3 class="mb-0 fw-bold">₦{{ number_format($walletBalance, 2) }}</h3>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-8 col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Top up Wallet</h3>
                                    </div>
                                    <div class="card-body">
                                        <p class="text-muted small mb-3">Add funds to your wallet to pay for orders at checkout.</p>
                                        <form action="{{ route('wallet.top-up') }}" method="POST" class="row g-2 align-items-end" enctype="multipart/form-data">
                                            @csrf
                                            <div class="col-auto">
                                                <label class="form-label mb-0">Amount (₦)</label>
                                                <input type="text" id="walletAmountDisplay" class="form-control" inputmode="decimal" placeholder="e.g. 2,000.00" autocomplete="off" required>
                                                <input type="hidden" name="amount" id="walletAmount" value="">
                                                @error('amount')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-auto">
                                                <label class="form-label mb-0">Payment Proof</label>
                                                <input type="file" name="proof" class="form-control" accept="image/*,.pdf" required>
                                                @error('proof')
                                                    <span class="text-danger small">{{ $message }}</span>
                                                @enderror
                                                <div class="form-text">Upload receipt/screenshot (JPG/PNG/PDF).</div>
                                            </div>
                                            <div class="col-auto">
                                                <button type="submit" class="btn btn-primary"><i class="fe fe-upload me-1"></i> Submit Proof</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h3 class="card-title">Transaction History</h3>
                                    </div>
                                    <div class="card-body p-0">
                                        @if($transactions->isEmpty())
                                            <p class="text-muted p-4 mb-0">No transactions yet. Top up your wallet to get started.</p>
                                        @else
                                            <div class="table-responsive">
                                                <table class="table table-hover mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Type</th>
                                                            <th>Status</th>
                                                            <th>Reference</th>
                                                            <th class="text-end">Amount</th>
                                                            <th class="text-end">Balance After</th>
                                                            <th>Proof</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($transactions as $tx)
                                                        <tr>
                                                            <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                                            <td>
                                                                @if($tx->isCredit())
                                                                    <span class="badge bg-success">Credit</span>
                                                                @else
                                                                    <span class="badge bg-danger">Debit</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if($tx->status === \App\Models\WalletTransaction::STATUS_PENDING)
                                                                    <span class="badge bg-warning">Pending</span>
                                                                @elseif($tx->status === \App\Models\WalletTransaction::STATUS_REJECTED)
                                                                    <span class="badge bg-danger">Rejected</span>
                                                                @else
                                                                    <span class="badge bg-success">Accepted</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ $tx->reference ?? '–' }}</td>
                                                            <td class="text-end {{ $tx->isCredit() ? 'text-success' : 'text-danger' }}">
                                                                {{ $tx->isCredit() ? '+' : '-' }}₦{{ number_format($tx->amount, 2) }}
                                                            </td>
                                                            <td class="text-end">₦{{ number_format($tx->balance_after ?? 0, 2) }}</td>
                                                            <td>
                                                                @if($tx->proof_path)
                                                                    <a href="{{ url('api/v1/storage/' . $tx->proof_path) }}" target="_blank">View</a>
                                                                @else
                                                                    <span class="text-muted">—</span>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
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
    <script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidemenu/sidemenu.js') }}"></script>
    <script src="{{ asset('sash/assets/plugins/sidebar/sidebar.js') }}"></script>
    <script src="{{ asset('sash/assets/js/themeColors.js') }}"></script>
    <script src="{{ asset('sash/assets/js/sticky.js') }}"></script>
    <script src="{{ asset('sash/assets/js/custom.js') }}"></script>
    <script>
        document.getElementById('year').textContent = new Date().getFullYear();

        (function () {
            const display = document.getElementById('walletAmountDisplay');
            const hidden = document.getElementById('walletAmount');
            if (!display || !hidden) return;

            function parseMoney(str) {
                // Allow commas while typing, but only keep digits + one dot.
                let cleaned = String(str || '').replace(/,/g, '').replace(/[^0-9.]/g, '');
                const firstDot = cleaned.indexOf('.');
                if (firstDot !== -1) {
                    cleaned = cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, '');
                }
                const num = Number(cleaned);
                return Number.isFinite(num) ? num : 0;
            }

            function formatMoney(num) {
                return Number(num || 0).toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                });
            }

            function syncHidden() {
                const num = parseMoney(display.value);
                hidden.value = num > 0 ? num.toFixed(2) : '';
            }

            display.addEventListener('input', function () {
                // typing-friendly: allow numbers, comma, dot
                const raw = String(display.value || '').replace(/[^0-9.,]/g, '');
                display.value = raw;

                const num = parseMoney(raw);
                if (raw.trim() === '' || raw === '.' || raw === ',') {
                    hidden.value = '';
                    return;
                }

                syncHidden();
            });

            display.addEventListener('blur', function () {
                const num = parseMoney(display.value);
                display.value = num > 0 ? formatMoney(num) : '';
                syncHidden();
            });

            // initialize
            display.value = '';
            hidden.value = '';
        })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
