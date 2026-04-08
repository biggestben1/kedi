<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>DPBV Spending History – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
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
                            <img src="{{ asset('images/logo.png') }}?v=3" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                        </a>
                        <div class="main-header-center ms-3 d-none d-lg-block">
                            <a href="{{ url('/') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                        </div>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-dpbv-spending" aria-controls="navbarSupportedContent-dpbv-spending" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-dpbv-spending">
                                    <div class="d-flex order-lg-2">
                                        <a class="nav-link icon text-center" href="{{ url('/') }}">
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
                                                <a class="dropdown-item" href="{{ route('dpbv.index') }}"><i class="dropdown-icon fe fe-award"></i> My DPBV</a>
                                                <a class="dropdown-item" href="{{ route('promo.index') }}"><i class="dropdown-icon fe fe-gift"></i> My Promo</a>
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                                                @php
                                                    $dpbvRole = auth()->user()->role?->name;
                                                    $dpbvAdminLabel = match($dpbvRole) {
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
                                                <a class="dropdown-item" href="{{ in_array($dpbvRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> {{ $dpbvAdminLabel }}</a>
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
                            <img src="{{ asset('images/logo.png') }}?v=3" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
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
                                <a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('dpbv.index') }}"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">My DPBV</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('dpbv.spending') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">DPBV Spending</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'reseller' || auth()->user()->role?->name === 'accountant' || auth()->user()->role?->name === 'dispatch' || auth()->user()->role?->name === 'headquarters' || auth()->user()->role?->name === 'branch' || auth()->user()->role?->name === 'service_center' || auth()->user()->role?->name === 'annex')
                            @php
                                $dpbvSideRole = auth()->user()->role?->name;
                                $dpbvSideAdminLabel = match($dpbvSideRole) {
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
                                <a class="side-menu__item" href="{{ in_array($dpbvSideRole, ['headquarters', 'branch', 'service_center', 'annex']) ? route('admin.pharmacy.dashboard') : route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">{{ $dpbvSideAdminLabel }}</span></a>
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
                            <h1 class="page-title">DPBV Spending History</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item"><a href="{{ route('dpbv.index') }}">My DPBV</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">Spending History</li>
                                </ol>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h3 class="card-title mb-0">Spending Summary</h3>
                                <div>
                                    <a href="{{ route('dpbv.index') }}" class="btn btn-sm btn-outline-primary"><i class="fe fe-arrow-left me-1"></i>Back to DPBV</a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-warning mb-0">
                                            <h5 class="alert-heading mb-2"><i class="fe fe-info me-2"></i>Total Spent</h5>
                                            <p class="mb-0">
                                                <strong>DPBV Spent:</strong> {{ number_format($totalSpent ?? 0, 2) }}<br>
                                                <strong class="text-danger fs-5">Naira Value: ₦{{ number_format($totalSpentNaira ?? 0, 2) }}</strong>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h3 class="card-title mb-0">DPBV Deductions</h3>
                            </div>
                            <div class="card-body p-0">
                                @if($spending->isEmpty())
                                    <p class="text-muted p-4 mb-0">No DPBV spending records found.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>KD NO</th>
                                                    <th>Name</th>
                                                    <th>Source</th>
                                                    <th class="text-end">DPBV Deducted</th>
                                                    <th class="text-end">Naira Value</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($spending as $s)
                                                <tr>
                                                    <td>{{ $s->record_date->format('M d, Y') }}</td>
                                                    <td><strong>{{ $s->code }}</strong></td>
                                                    <td>{{ $s->name }}</td>
                                                    <td>
                                                        @if($s->sc === 'CHECKOUT')
                                                            <span class="badge bg-info">Checkout Payment</span>
                                                        @elseif($s->sc === 'DPBV_ORDER')
                                                            <span class="badge bg-success">DPBV Order</span>
                                                        @else
                                                            <span class="badge bg-secondary">{{ $s->sc }}</span>
                                                        @endif
                                                    </td>
                                                    <td class="text-end text-danger">{{ number_format(abs($s->dpbv), 2) }}</td>
                                                    <td class="text-end">₦{{ number_format((abs($s->dpbv) * 0.95) * 990, 2) }}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                            @if(!$spending->isEmpty() && $spending->hasPages())
                                <div class="card-footer">{{ $spending->links() }}</div>
                            @endif
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title mb-0">Orders Paid with DPBV</h3>
                            </div>
                            <div class="card-body p-0">
                                @if($dpbvOrders->isEmpty())
                                    <p class="text-muted p-4 mb-0">No orders found that were paid with DPBV.</p>
                                @else
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0">
                                            <thead>
                                                <tr>
                                                    <th>Order Number</th>
                                                    <th>Date</th>
                                                    <th>Items</th>
                                                    <th>Status</th>
                                                    <th class="text-end">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dpbvOrders as $order)
                                                <tr>
                                                    <td><strong>{{ $order->invoice_number ?? 'ORD-' . $order->id }}</strong></td>
                                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                                    <td>
                                                        @foreach($order->items->take(3) as $item)
                                                            {{ $item->product_name }} ({{ $item->quantity }})<br>
                                                        @endforeach
                                                        @if($order->items->count() > 3)
                                                            <small class="text-muted">+{{ $order->items->count() - 3 }} more</small>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $order->status === 'paid' ? 'success' : ($order->status === 'pending' ? 'warning' : 'secondary') }}">
                                                            {{ ucfirst($order->status) }}
                                                        </span>
                                                    </td>
                                                    <td class="text-end">
                                                        <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary">View</a>
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
    @include('partials.pwa-scripts')
</body>
</html>
