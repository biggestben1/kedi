<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') – {{ config('app.name', 'Laravel') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}" />
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/dark-style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/transparent-style.css') }}" rel="stylesheet">
    <link href="{{ asset('sash/assets/css/skin-modes.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
    <style>
        /* Admin: single logo in sidebar only; hide header logo */
        .app-header .logo-horizontal {
            display: none !important;
        }
        .app-sidebar .side-header .header-brand1 img,
        .app-sidebar .side-header .header-brand-img {
            max-height: 58px !important;
            max-width: 100% !important;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
            display: block !important;
            visibility: visible !important;
        }
        .app-sidebar .side-header {
            padding: 14px 15px !important;
            min-height: auto !important;
        }
        /* Sidebar: fixed height and scrollable menu */
        .app-sidebar {
            height: 100vh;
            display: flex !important;
            flex-direction: column !important;
            overflow: hidden !important;
        }
        .app-sidebar .side-header {
            flex-shrink: 0;
        }
        .app-sidebar .main-sidemenu {
            flex: 1;
            min-height: 0;
            overflow-y: auto !important;
            overflow-x: hidden;
        }
        /* Prevent header hamburger from overlapping sidebar menu on small screens */
        @media (max-width: 991px) {
            .app-sidebar {
                padding-top: 58px;
            }
            .app-sidebar .side-header {
                padding-top: 12px !important;
            }
        }
    </style>
</head>
<body class="app sidebar-mini ltr light-mode">
    <div id="global-loader">
        <img src="{{ asset('sash/assets/images/loader.svg') }}" class="loader-img" alt="Loader">
    </div>

    <div class="page">
        <div class="page-main">
            <!-- app-Header -->
            <div class="app-header header sticky">
                <div class="container-fluid main-container">
                    <div class="d-flex">
                        <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                        <a class="logo-horizontal" href="{{ route('admin') }}">
                            <img src="{{ asset('images/logo.png') }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}';">
                        </a>
                        <div class="d-flex order-lg-2 ms-auto header-right-icons">
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-admin" aria-controls="navbarSupportedContent-admin" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-admin">
                                    <div class="d-flex order-lg-2">
                                        <div class="dropdown d-flex">
                                            <a class="nav-link icon theme-layout nav-link-bg layout-setting" href="javascript:void(0)">
                                                <span class="dark-layout"><i class="fe fe-moon"></i></span>
                                                <span class="light-layout"><i class="fe fe-sun"></i></span>
                                            </a>
                                        </div>
                                        <div class="dropdown d-flex profile-1">
                                            <a href="javascript:void(0)" data-bs-toggle="dropdown" class="nav-link leading-none d-flex">
                                                <span class="avatar profile-user brround cover-image bg-primary text-white d-flex align-items-center justify-content-center">{{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}</span>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                                                <div class="drop-heading">
                                                    <div class="text-center">
                                                        <h5 class="text-dark mb-0 fs-14 fw-semibold">{{ auth()->user()->name }}</h5>
                                                        <small class="text-muted">{{ auth()->user()->email }}</small>
                                                    </div>
                                                </div>
                                                <div class="dropdown-divider m-0"></div>
                                                <a class="dropdown-item" href="{{ route('home') }}"><i class="dropdown-icon fe fe-shopping-bag"></i> Back to Shop</a>
                                                <a class="dropdown-item" href="{{ route('dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                                @if(auth()->user()->role?->name !== 'reseller')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Admin</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="dropdown-icon fe fe-users"></i> Users</a>
                                                @else
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Reseller</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'customer', 'created_by' => auth()->id()]) }}"><i class="dropdown-icon fe fe-users"></i> My Customers</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> Invoices</a>
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
            <!-- /app-Header -->

            <!-- APP-SIDEBAR -->
            <div class="sticky">
                <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
                <div class="app-sidebar">
                    <div class="side-header">
                        <a class="header-brand1" href="{{ route('admin') }}">
                            <img src="{{ asset('images/logo.png') }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}';">
                        </a>
                    </div>
                    <div class="main-sidemenu">
                        <div class="slide-left disabled" id="slide-left"><svg xmlns="http://www.w3.org/2000/svg" fill="#7b8191" width="24" height="24" viewBox="0 0 24 24"><path d="M13.293 6.293 7.586 12l5.707 5.707 1.414-1.414L10.414 12l4.293-4.293z"/></svg></div>
                        <ul class="side-menu">
                            @if(auth()->user()->role?->name === 'reseller')
                            {{-- Reseller menu: Users (customers only), Invoices --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index', ['role' => 'customer', 'created_by' => auth()->id()]) }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="sub-category"><h3>Reseller</h3></li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index', ['role' => 'customer', 'created_by' => auth()->id()]) }}" class="slide-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">My Customers</a></li>
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Create Customer</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'dispatch')
                            {{-- Dispatch menu: Orders, Products (view only) --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.dispatch.orders*') ? 'active' : '' }}" href="{{ route('admin.dispatch.orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}" href="{{ route('admin.products.index') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Products (Stock)</span></a>
                            </li>
                            @else
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="sub-category"><h3>Admin</h3></li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.reports*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.reports') }}"><i class="side-menu__icon fe fe-bar-chart-2"></i><span class="side-menu__label">Pharmacy Reports</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Create</a></li>
                                    @if(auth()->user()->isSuperAdmin())
                                    <li><a href="{{ route('admin.users.index', ['role' => 'super_admin']) }}" class="slide-item {{ request()->query('role') === 'super_admin' ? 'active' : '' }}">Super Admin</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'wholesale_staff']) }}" class="slide-item {{ request()->query('role') === 'wholesale_staff' ? 'active' : '' }}">Wholesale Staff</a></li>
                                    @endif
                                    <li><a href="{{ route('admin.users.index', ['role' => 'reseller']) }}" class="slide-item {{ request()->query('role') === 'reseller' ? 'active' : '' }}">Reseller</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'customer']) }}" class="slide-item {{ request()->query('role') === 'customer' ? 'active' : '' }}">Customer</a></li>
                                    @if(auth()->user()->isSuperAdmin())
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    @endif
                                </ul>
                            </li>
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Products</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'open' : '' }}" style="{{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.categories.index') }}" class="slide-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Categories</a></li>
                                    <li><a href="{{ route('admin.products.index') }}" class="slide-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">Products</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.suppliers*') ? 'active' : '' }}" href="{{ route('admin.suppliers.index') }}"><i class="side-menu__icon fe fe-truck"></i><span class="side-menu__label">Suppliers</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.purchases*') ? 'active' : '' }}" href="{{ route('admin.purchases.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchases</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'accountant')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.banks*') ? 'active' : '' }}" href="{{ route('admin.banks.index') }}"><i class="side-menu__icon fe fe-briefcase"></i><span class="side-menu__label">Banks</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'accountant')
                            <li class="slide {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Wallet Management</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.accountant.wallet.index') }}" class="slide-item {{ request()->routeIs('admin.accountant.wallet.index') ? 'active' : '' }}">All Transactions</a></li>
                                    <li><a href="{{ route('admin.accountant.wallet.users') }}" class="slide-item {{ request()->routeIs('admin.accountant.wallet.users') ? 'active' : '' }}">User Balances</a></li>
                                    <li><a href="{{ route('admin.wallet_topups') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups') ? 'active' : '' }}">Pending Top-ups</a></li>
                                    <li><a href="{{ route('admin.wallet_topups.approved') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups.approved') ? 'active' : '' }}">Approved Top-ups</a></li>
                                    <li><a href="{{ route('admin.wallet_topups.rejected') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups.rejected') ? 'active' : '' }}">Rejected Top-ups</a></li>
                                </ul>
                            </li>
                            @endif
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
            <!--/APP-SIDEBAR-->

            <div class="main-content app-content mt-0">
                <div class="side-app">
                    <div class="main-container container-fluid">
                        @yield('content')
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
    <script>document.getElementById('year').textContent = new Date().getFullYear();</script>
    @stack('scripts')
</body>
</html>
