<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') }}?v=3" />
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
            background-color: #fff !important;
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
                            <img src="{{ asset('images/logo.png') }}?v=3" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}?v=3';">
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
                                                <a class="dropdown-item" href="{{ route('password.change') }}"><i class="dropdown-icon fe fe-lock"></i> Change Password</a>
                                                @if(auth()->user()->role?->name === 'reseller')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Reseller</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index', ['role' => 'customer']) }}"><i class="dropdown-icon fe fe-users"></i> My Customers</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> Invoices</a>
                                                @elseif(auth()->user()->role?->name === 'headquarters')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Headquarters</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="dropdown-icon fe fe-users"></i> My Users</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> Invoices</a>
                                                <a class="dropdown-item" href="{{ route('admin.banks.index') }}"><i class="dropdown-icon fe fe-credit-card"></i> Banks</a>
                                                @elseif(auth()->user()->role?->name === 'branch')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Branch</a>
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                                <a class="dropdown-item" href="{{ route('admin.branch.stock.index') }}"><i class="dropdown-icon fe fe-package"></i> My Stock</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="dropdown-icon fe fe-users"></i> My Users</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> Invoices</a>
                                                <a class="dropdown-item" href="{{ route('admin.banks.index') }}"><i class="dropdown-icon fe fe-credit-card"></i> Banks</a>
                                                @elseif(auth()->user()->role?->name === 'service_center')
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.reports') }}"><i class="dropdown-icon fe fe-bar-chart-2"></i> Reports</a>
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Admin</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> Invoices</a>
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="dropdown-icon fe fe-users"></i> Referred Orders</a>
                                                <a class="dropdown-item" href="{{ route('admin.products.index') }}"><i class="dropdown-icon fe fe-grid"></i> Products</a>
                                                @elseif(auth()->user()->role?->name === 'annex')
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.dashboard') }}"><i class="dropdown-icon fe fe-grid"></i> Dashboard</a>
                                                <a class="dropdown-item" href="{{ route('admin.pharmacy.reports') }}"><i class="dropdown-icon fe fe-bar-chart-2"></i> Reports</a>
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Admin</a>
                                                <a class="dropdown-item" href="{{ route('admin.invoices.index') }}"><i class="dropdown-icon fe fe-file-text"></i> My Invoices</a>
                                                <a class="dropdown-item" href="{{ route('admin.products.index') }}"><i class="dropdown-icon fe fe-grid"></i> Products</a>
                                                @elseif(auth()->user()->role?->name === 'accountant')
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Admin</a>
                                                <a class="dropdown-item" href="{{ route('admin.banks.index') }}"><i class="dropdown-icon fe fe-credit-card"></i> Banks</a>
                                                <a class="dropdown-item" href="{{ route('admin.accountant.wallet.index') }}"><i class="dropdown-icon fe fe-wallet"></i> Wallet Management</a>
                                                @else
                                                <a class="dropdown-item" href="{{ route('admin') }}"><i class="dropdown-icon fe fe-settings"></i> Admin</a>
                                                <a class="dropdown-item" href="{{ route('admin.users.index') }}"><i class="dropdown-icon fe fe-users"></i> Users</a>
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
                            <img src="{{ asset('images/logo.png') }}?v=3" class="header-brand-img light-logo1" alt="{{ config('app.name') }}" onerror="this.onerror=null;this.src='{{ asset('sash/assets/images/brand/logo.png') }}?v=3';">
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
                                <a class="side-menu__item {{ request()->routeIs('admin') && !request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.users*') ? 'active' : '' }}" href="{{ route('admin.users.index', ['role' => 'customer']) }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="sub-category"><h3>Reseller</h3></li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index', ['role' => 'customer']) }}" class="slide-item {{ request()->routeIs('admin.users.index') ? 'active' : '' }}">My Customers</a></li>
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Create Customer</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}"><i class="side-menu__icon fe fe-mail"></i><span class="side-menu__label">Contact Us</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') && !request()->routeIs('admin.kedi-kits.purchase.seller*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'dispatch')
                            {{-- Dispatch menu: Orders, Products (view only) --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.dispatch.orders*') ? 'active' : '' }}" href="{{ route('admin.dispatch.orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}" href="{{ route('admin.products.index') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Products (Stock)</span></a>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'headquarters')
                            {{-- Headquarters menu: All invoices, Products CRUD, Categories CRUD --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="sub-category"><h3>Headquarters</h3></li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.referred-orders*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referred Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.back_orders*') ? 'active' : '' }}" href="{{ route('admin.back_orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Back Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.dispatch.orders*') ? 'active' : '' }}" href="{{ route('admin.dispatch.orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Dispatch Orders</span></a>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.promo*') ? 'active' : '' }}" href="{{ route('admin.promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">Promo Upload</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') && !request()->routeIs('admin.kedi-kits.purchase.seller*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
                            <li class="slide">
                                
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index') }}" class="slide-item {{ request()->routeIs('admin.users.index') && empty(request('role')) ? 'active' : '' }}">My Users</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'branch']) }}" class="slide-item {{ request()->query('role') === 'branch' ? 'active' : '' }}">Branch</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="slide-item {{ request()->query('role') === 'annex' ? 'active' : '' }}">Annex</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="slide-item {{ request()->query('role') === 'service_center' ? 'active' : '' }}">Service Center</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Create User</a></li>
                                </ul>
                            </li>
                            <li class="slide {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Products</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'open' : '' }}" style="{{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.categories.index') }}" class="slide-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Categories</a></li>
                                    <li><a href="{{ route('admin.products.index') }}" class="slide-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">Products</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.banks*') ? 'active' : '' }}" href="{{ route('admin.banks.index') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Banks</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-wallet"></i><span class="side-menu__label">Wallet</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.accountant.wallet.index') }}" class="slide-item {{ request()->routeIs('admin.accountant.wallet.index') ? 'active' : '' }}">Wallet Management</a></li>
                                    <li><a href="{{ route('admin.wallet_topups') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups*') ? 'active' : '' }}">Top-up Approvals</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}"><i class="side-menu__icon fe fe-mail"></i><span class="side-menu__label">Contact Us</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'branch')
                            {{-- Branch menu: like Headquarters but cannot create Branch users --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.branch.stock*') ? 'active' : '' }}" href="{{ route('admin.branch.stock.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Stock</span></a>
                            </li>
                            <li class="sub-category"><h3>Branch</h3></li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.referred-orders*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referred Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.back_orders*') ? 'active' : '' }}" href="{{ route('admin.back_orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Back Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.dispatch.orders*') ? 'active' : '' }}" href="{{ route('admin.dispatch.orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Dispatch Orders</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index') }}" class="slide-item {{ request()->routeIs('admin.users.index') && empty(request('role')) ? 'active' : '' }}">My Users</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="slide-item {{ request()->query('role') === 'annex' ? 'active' : '' }}">Annex</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="slide-item {{ request()->query('role') === 'service_center' ? 'active' : '' }}">Service Center</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    <li><a href="{{ route('admin.users.create', ['role' => 'annex']) }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') === 'annex' ? 'active' : '' }}">Create Annex</a></li>
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') !== 'annex' ? 'active' : '' }}">Create User</a></li>
                                </ul>
                            </li>
                            <li class="slide {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Products</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'open' : '' }}" style="{{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*')) ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.categories.index') }}" class="slide-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Categories</a></li>
                                    <li><a href="{{ route('admin.products.index') }}" class="slide-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">Products</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.banks*') ? 'active' : '' }}" href="{{ route('admin.banks.index') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Banks</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-wallet"></i><span class="side-menu__label">Wallet</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.accountant.wallet.index') }}" class="slide-item {{ request()->routeIs('admin.accountant.wallet.index') ? 'active' : '' }}">Wallet Management</a></li>
                                    <li><a href="{{ route('admin.wallet_topups') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups*') ? 'active' : '' }}">Top-up Approvals</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}"><i class="side-menu__icon fe fe-mail"></i><span class="side-menu__label">Contact Us</span></a>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') && !request()->routeIs('admin.kedi-kits.purchase.seller*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'service_center')
                            {{-- Service Center menu: Dashboard, Reports, Banks, Wallet, Users, Invoices, Back Orders --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="sub-category"><h3>Service Center</h3></li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.reports*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.reports') }}"><i class="side-menu__icon fe fe-bar-chart-2"></i><span class="side-menu__label">Reports</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.referred-orders*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referred Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.back_orders*') ? 'active' : '' }}" href="{{ route('admin.back_orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Back Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}" href="{{ route('admin.products.index') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Products (View only)</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index') }}" class="slide-item {{ request()->routeIs('admin.users.index') && empty(request('role')) ? 'active' : '' }}">My Users</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="slide-item {{ request()->query('role') === 'annex' ? 'active' : '' }}">Annex</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.create', ['role' => 'annex']) }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') === 'annex' ? 'active' : '' }}">Create Annex</a></li>
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') !== 'annex' ? 'active' : '' }}">Create User</a></li>
                                </ul>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.banks*') ? 'active' : '' }}" href="{{ route('admin.banks.index') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Banks</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-wallet"></i><span class="side-menu__label">Wallet</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.accountant.wallet*') || request()->routeIs('admin.wallet_topups*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.accountant.wallet.index') }}" class="slide-item {{ request()->routeIs('admin.accountant.wallet.index') ? 'active' : '' }}">Wallet Management</a></li>
                                    <li><a href="{{ route('admin.wallet_topups') }}" class="slide-item {{ request()->routeIs('admin.wallet_topups*') ? 'active' : '' }}">Top-up Approvals</a></li>
                                </ul>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') && !request()->routeIs('admin.kedi-kits.purchase.seller*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
                            @elseif(auth()->user()->role?->name === 'annex')
                            {{-- Annex menu: Dashboard, Reports, Users (Accountant, Dispatch), Products (view only), Invoices (own), Back Orders (own) --}}
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="sub-category"><h3>Annex</h3></li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.referred-orders*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referred Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.reports*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.reports') }}"><i class="side-menu__icon fe fe-bar-chart-2"></i><span class="side-menu__label">Reports</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">My Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.back_orders*') ? 'active' : '' }}" href="{{ route('admin.back_orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Back Orders</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.products.index') ? 'active' : '' }}" href="{{ route('admin.products.index') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Products (View only)</span></a>
                            </li>
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.index') }}" class="slide-item {{ request()->routeIs('admin.users.index') && empty(request('role')) ? 'active' : '' }}">My Users</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    <li><a href="{{ route('admin.users.create', ['role' => 'accountant']) }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') === 'accountant' ? 'active' : '' }}">Create Accountant</a></li>
                                    <li><a href="{{ route('admin.users.create', ['role' => 'dispatch']) }}" class="slide-item {{ request()->routeIs('admin.users.create') && request()->query('role') === 'dispatch' ? 'active' : '' }}">Create Dispatch</a></li>
                                </ul>
                            </li>
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
                            @else
                            <li class="sub-category"><h3>Main</h3></li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('home') }}"><i class="side-menu__icon fe fe-shopping-bag"></i><span class="side-menu__label">Back to Shop</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item" href="{{ route('admin') }}"><i class="side-menu__icon fe fe-settings"></i><span class="side-menu__label">Admin</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.dashboard') ? 'active' : '' }}" href="{{ route('admin.pharmacy.dashboard') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Dashboard</span></a>
                            </li>
                            <li class="sub-category"><h3>Admin</h3></li>
                            @if(auth()->user()->role?->name !== 'accountant')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.reports*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.reports') }}"><i class="side-menu__icon fe fe-bar-chart-2"></i><span class="side-menu__label">Pharmacy Reports</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.pharmacy.referred-orders*') ? 'active' : '' }}" href="{{ route('admin.pharmacy.referred-orders') }}"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Referred Orders</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide {{ request()->routeIs('admin.dispatch.orders*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-truck"></i><span class="side-menu__label">Dispatch</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.dispatch.orders*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.dispatch.orders*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.dispatch.orders.index') }}" class="slide-item {{ request()->routeIs('admin.dispatch.orders.index') && request()->query('status') !== 'completed' ? 'active' : '' }}">All Orders</a></li>
                                    <li><a href="{{ route('admin.dispatch.orders.index', ['status' => 'completed']) }}" class="slide-item {{ request()->routeIs('admin.dispatch.orders.index') && request()->query('status') === 'completed' ? 'active' : '' }}">Completed Orders</a></li>
                                </ul>
                            </li>
                            @endif
                            @if(auth()->user()->role?->name !== 'accountant')
                            <li class="slide {{ request()->routeIs('admin.users*') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-users"></i><span class="side-menu__label">Users</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.users*') ? 'open' : '' }}" style="{{ request()->routeIs('admin.users*') ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.users.create') }}" class="slide-item {{ request()->routeIs('admin.users.create') ? 'active' : '' }}">Create</a></li>
                                    @if(auth()->user()->isSuperAdmin())
                                    <li><a href="{{ route('admin.users.index', ['role' => 'super_admin']) }}" class="slide-item {{ request()->query('role') === 'super_admin' ? 'active' : '' }}">Super Admin</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="slide-item {{ request()->query('role') === 'accountant' ? 'active' : '' }}">Accountant</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="slide-item {{ request()->query('role') === 'dispatch' ? 'active' : '' }}">Dispatch</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'headquarters']) }}" class="slide-item {{ request()->query('role') === 'headquarters' ? 'active' : '' }}">Headquarters</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'branch']) }}" class="slide-item {{ request()->query('role') === 'branch' ? 'active' : '' }}">Branch</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="slide-item {{ request()->query('role') === 'annex' ? 'active' : '' }}">Annex</a></li>
                                    <li><a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="slide-item {{ request()->query('role') === 'service_center' ? 'active' : '' }}">Service Center</a></li>
                                    @endif
                                </ul>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.roles*') ? 'active' : '' }}" href="{{ route('admin.roles.index') }}"><i class="side-menu__icon fe fe-shield"></i><span class="side-menu__label">Roles</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.announcements*') ? 'active' : '' }}" href="{{ route('admin.announcements.index') }}"><i class="side-menu__icon fe fe-bell"></i><span class="side-menu__label">Announcements</span></a>
                            </li>
                            <li class="slide {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*') || request()->routeIs('admin.in-stock*')) ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Products</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*') || request()->routeIs('admin.in-stock*')) ? 'open' : '' }}" style="{{ (request()->routeIs('admin.categories*') || request()->routeIs('admin.products*') || request()->routeIs('admin.in-stock*')) ? 'display: block;' : '' }}">
                                    <li><a href="{{ route('admin.categories.index') }}" class="slide-item {{ request()->routeIs('admin.categories*') ? 'active' : '' }}">Categories</a></li>
                                    <li><a href="{{ route('admin.products.index') }}" class="slide-item {{ request()->routeIs('admin.products*') ? 'active' : '' }}">Products</a></li>
                                    @if(auth()->user()->isSuperAdmin())
                                    <li><a href="{{ route('admin.in-stock.index') }}" class="slide-item {{ request()->routeIs('admin.in-stock*') ? 'active' : '' }}">In Stock</a></li>
                                    @endif
                                </ul>
                            </li>
                            
                            @if(auth()->user()->role?->name !== 'annex')
                            <li class="slide {{ request()->routeIs('admin.dpbv*') || request()->routeIs('dpbv.index') ? 'is-expanded' : '' }}">
                                <a class="side-menu__item" data-bs-toggle="slide" href="javascript:void(0)"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">DPBV</span><i class="angle fe fe-chevron-right"></i></a>
                                <ul class="slide-menu {{ request()->routeIs('admin.dpbv*') || request()->routeIs('dpbv.index') ? 'open' : '' }}" style="{{ request()->routeIs('admin.dpbv*') || request()->routeIs('dpbv.index') ? 'display: block;' : '' }}">
                                    @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'headquarters')
                                    <li><a href="{{ route('admin.dpbv.index') }}" class="slide-item {{ request()->routeIs('admin.dpbv*') ? 'active' : '' }}">DPBV Upload</a></li>
                                    @endif
                                    <li><a href="{{ route('dpbv.index') }}" class="slide-item {{ request()->routeIs('dpbv.index') ? 'active' : '' }}">My DPBV</a></li>
                                </ul>
                            </li>
                            @endif
                            
                            
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff' || auth()->user()->role?->name === 'accountant')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.banks*') ? 'active' : '' }}" href="{{ route('admin.banks.index') }}"><i class="side-menu__icon fe fe-credit-card"></i><span class="side-menu__label">Banks</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.expenditures*') ? 'active' : '' }}" href="{{ route('admin.expenditures.index') }}"><i class="side-menu__icon fe fe-clipboard"></i><span class="side-menu__label">Expenditures</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.coupons*') ? 'active' : '' }}" href="{{ route('admin.coupons.index') }}"><i class="side-menu__icon fe fe-tag"></i><span class="side-menu__label">Coupons</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.index') }}"><i class="side-menu__icon fe fe-box"></i><span class="side-menu__label">KEDI Kits</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'wholesale_staff')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.invoices*') ? 'active' : '' }}" href="{{ route('admin.invoices.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">Invoices</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.back_orders*') ? 'active' : '' }}" href="{{ route('admin.back_orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">Back Orders</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin() || auth()->user()->role?->name === 'headquarters')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.promo*') ? 'active' : '' }}" href="{{ route('admin.promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">Promo Upload</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->isSuperAdmin())
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.bonus*') ? 'active' : '' }}" href="{{ route('admin.bonus.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Bonus Disbursed</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->role?->name === 'accountant')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd*') ? 'active' : '' }}" href="{{ route('admin.kd.index') }}"><i class="side-menu__icon fe fe-hash"></i><span class="side-menu__label">Borrow</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kd.registration*') ? 'active' : '' }}" href="{{ route('admin.kd.registration.index') }}"><i class="side-menu__icon fe fe-file-text"></i><span class="side-menu__label">KD Registration</span></a>
                            </li>
                            @endif
                            @if(auth()->user()->role?->name !== 'accountant')
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.contacts*') ? 'active' : '' }}" href="{{ route('admin.contacts.index') }}"><i class="side-menu__icon fe fe-mail"></i><span class="side-menu__label">Contact Us</span></a>
                            </li>
                            @endif
                            
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('promo.index') ? 'active' : '' }}" href="{{ route('promo.index') }}"><i class="side-menu__icon fe fe-gift"></i><span class="side-menu__label">My Promo</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('bonus.index') ? 'active' : '' }}" href="{{ route('bonus.index') }}"><i class="side-menu__icon fe fe-trending-up"></i><span class="side-menu__label">My Bonus</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item {{ request()->routeIs('admin.kedi-kits.purchase*') && !request()->routeIs('admin.kedi-kits.purchase.seller*') ? 'active' : '' }}" href="{{ route('admin.kedi-kits.purchase.index') }}"><i class="side-menu__icon fe fe-shopping-cart"></i><span class="side-menu__label">Purchase Kits</span></a>
                            </li>
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
                                <a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-user"></i><span class="side-menu__label">Profile</span></a>
                            </li>
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
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible fade show mb-3" role="alert">
                                {{ session('success') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="alert alert-danger alert-dismissible fade show mb-3" role="alert">
                                {{ session('error') }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        @endif
                        
                        <div class="row">
                            @include('partials.announcements')
                        </div>

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
