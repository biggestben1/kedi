<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>My DPBV – {{ config('app.name') }}</title>
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
                            <button class="navbar-toggler navresponsive-toggler d-lg-none ms-auto" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent-dpbv" aria-controls="navbarSupportedContent-dpbv" aria-expanded="false" aria-label="Toggle navigation">
                                <span class="navbar-toggler-icon fe fe-more-vertical"></span>
                            </button>
                            <div class="navbar navbar-collapse responsive-navbar p-0">
                                <div class="collapse navbar-collapse" id="navbarSupportedContent-dpbv">
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
                                <a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a>
                            </li>
                            <li class="slide">
                                <a class="side-menu__item active" href="{{ route('dpbv.index') }}"><i class="side-menu__icon fe fe-award"></i><span class="side-menu__label">My DPBV</span></a>
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
                            <h1 class="page-title">My DPBV</h1>
                            <div>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="{{ url('/') }}">Home</a></li>
                                    <li class="breadcrumb-item active" aria-current="page">My DPBV</li>
                                </ol>
                            </div>
                        </div>
                        @if(session('success'))
                            <div class="alert alert-success">{{ session('success') }}</div>
                        @endif
                        @if($errors->any())
                            <div class="alert alert-danger">{{ $errors->first() }}</div>
                        @endif

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <h3 class="card-title mb-0">DPBV Collection</h3>
                                <div>
                                    <span class="badge bg-primary fs-6">Total DPBV: <span id="dpbv-total-badge">{{ number_format($totalDpbv ?? 0, 2) }}</span></span>
                                    <a href="{{ route('dpbv.spending') }}" class="btn btn-sm btn-outline-info ms-2"><i class="fe fe-shopping-cart me-1"></i> View Spending</a>
                                    <a href="{{ url('/') }}" class="btn btn-sm btn-outline-primary ms-2"><i class="fe fe-shopping-bag me-1"></i> Back to Shop</a>
                                </div>
                            </div>
                            <div class="card-body border-bottom">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="alert alert-info mb-0">
                                            <h5 class="alert-heading mb-2"><i class="fe fe-info me-2"></i>Shopping Credit</h5>
                                            <p class="mb-2">Your DPBV can be used for shopping:</p>
                                            <p class="mb-0">
                                                <strong>Total DPBV:</strong> <span id="dpbv-total-text">{{ number_format($totalDpbv ?? 0, 2) }}</span><br>
                                                <strong>After 5% discount:</strong> <span id="dpbv-after-discount-text">{{ number_format(($totalDpbv ?? 0) * 0.95, 2) }}</span><br>
                                                <strong class="text-success fs-5">Naira Equivalent: <span id="dpbv-naira-text">₦{{ number_format($nairaEquivalent ?? 0, 2) }}</span></strong>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mt-3 mt-md-0">
                                        <form id="dpbv-search-form" method="GET" action="{{ route('dpbv.index') }}" class="d-flex gap-2">
                                            <input
                                                id="dpbv-search-input"
                                                type="text"
                                                name="search"
                                                value="{{ $search ?? '' }}"
                                                class="form-control"
                                                placeholder="Search by KD NO, name, SC, or date (YYYY-MM-DD)">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fe fe-search me-1"></i>Search
                                            </button>
                                            @if(!empty($search))
                                                <a href="{{ route('dpbv.index') }}" id="dpbv-reset-link" class="btn btn-outline-secondary">Reset</a>
                                            @endif
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div id="dpbv-results">
                                @include('dpbv.partials.table', ['collections' => $collections, 'netByCode' => $netByCode ?? []])
                            </div>
                        </div>

                        <div class="modal fade" id="dpbvTransferModal" tabindex="-1" aria-labelledby="dpbvTransferModalLabel" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <form method="POST" action="{{ route('dpbv.transfer') }}">
                                        @csrf
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="dpbvTransferModalLabel">Transfer DPBV</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="source_id" id="transfer-source-id">
                                            <div class="mb-3">
                                                <label class="form-label">Code (KD NO)</label>
                                                <input type="text" class="form-control" id="transfer-code-display" readonly>
                                                <input type="hidden" name="code" id="transfer-code">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Name</label>
                                                <input type="text" class="form-control" id="transfer-name-display" readonly>
                                                <input type="hidden" name="name" id="transfer-name">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Recipient Email</label>
                                                <input type="email" class="form-control" name="recipient_email" id="transfer-recipient-email" required>
                                                <small id="transfer-recipient-feedback" class="d-block mt-1 text-muted"></small>
                                            </div>
                                            <div class="mb-2">
                                                <label class="form-label">DPBV Amount</label>
                                                <input type="number" class="form-control" name="amount" id="transfer-amount" step="0.01" min="0.01" required readonly>
                                                <small class="text-muted">Max for selected row: <span id="transfer-max-text">0.00</span></small>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Transfer</button>
                                        </div>
                                    </form>
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
        (function() {
            var form = document.getElementById('dpbv-search-form');
            var input = document.getElementById('dpbv-search-input');
            var results = document.getElementById('dpbv-results');
            var totalBadge = document.getElementById('dpbv-total-badge');
            var totalText = document.getElementById('dpbv-total-text');
            var afterDiscountText = document.getElementById('dpbv-after-discount-text');
            var nairaText = document.getElementById('dpbv-naira-text');
            var typingTimer;
            var activeController = null;
            var transferCode = document.getElementById('transfer-code');
            var transferCodeDisplay = document.getElementById('transfer-code-display');
            var transferName = document.getElementById('transfer-name');
            var transferNameDisplay = document.getElementById('transfer-name-display');
            var transferSourceId = document.getElementById('transfer-source-id');
            var transferAmount = document.getElementById('transfer-amount');
            var transferMaxText = document.getElementById('transfer-max-text');
            var transferRecipientEmail = document.getElementById('transfer-recipient-email');
            var transferRecipientFeedback = document.getElementById('transfer-recipient-feedback');
            var transferModalElement = document.getElementById('dpbvTransferModal');
            var transferModal = transferModalElement ? new bootstrap.Modal(transferModalElement) : null;
            var recipientTimer;

            if (!form || !input || !results) return;

            function formatNumber(value) {
                return Number(value || 0).toLocaleString('en-US', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function buildUrl(url) {
                var endpoint = new URL(url || form.action, window.location.origin);
                var q = (input.value || '').trim();
                if (q) {
                    endpoint.searchParams.set('search', q);
                } else {
                    endpoint.searchParams.delete('search');
                }
                return endpoint.toString();
            }

            function updateSummary(totalDpbv, nairaEquivalent) {
                var total = Number(totalDpbv || 0);
                var afterDiscount = total * 0.95;
                if (totalBadge) totalBadge.textContent = formatNumber(total);
                if (totalText) totalText.textContent = formatNumber(total);
                if (afterDiscountText) afterDiscountText.textContent = formatNumber(afterDiscount);
                if (nairaText) nairaText.textContent = '₦' + formatNumber(nairaEquivalent || 0);
            }

            function fetchResults(url) {
                var endpoint = buildUrl(url);
                if (activeController) {
                    activeController.abort();
                }
                activeController = new AbortController();
                fetch(endpoint, {
                    signal: activeController.signal,
                    headers: {
                        'Accept': 'application/json, text/plain, */*',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    if (!response.ok) throw new Error('Request failed');
                    return response.json();
                })
                .then(function(data) {
                    if (!data || typeof data.html === 'undefined') return;
                    results.innerHTML = data.html;
                    updateSummary(data.totalDpbv, data.nairaEquivalent);
                    history.replaceState({}, '', endpoint);
                    applyLocalFilter((input.value || '').trim().toLowerCase());
                })
                .catch(function(error) {
                    if (error && error.name === 'AbortError') return;
                    // Fallback to normal navigation if AJAX fails.
                    window.location.href = endpoint;
                });
            }

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                fetchResults(form.action);
            });

            function triggerLiveSearch() {
                var currentSearch = (input.value || '').trim().toLowerCase();
                applyLocalFilter(currentSearch);
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    fetchResults(form.action);
                }, 150);
            }

            function applyLocalFilter(term) {
                var rows = results.querySelectorAll('tr[data-dpbv-row]');
                if (!rows.length) return;
                rows.forEach(function(row) {
                    var haystack = (row.getAttribute('data-search') || '').toLowerCase();
                    var show = !term || haystack.indexOf(term) !== -1;
                    row.style.display = show ? '' : 'none';
                });
            }

            input.addEventListener('input', triggerLiveSearch);
            input.addEventListener('keyup', triggerLiveSearch);
            input.addEventListener('search', triggerLiveSearch);
            input.addEventListener('change', triggerLiveSearch);
            input.addEventListener('paste', triggerLiveSearch);

            results.addEventListener('click', function(e) {
                var transferBtn = e.target.closest('.js-transfer-btn');
                if (transferBtn && transferModal) {
                    var sourceId = transferBtn.getAttribute('data-id') || '';
                    var code = transferBtn.getAttribute('data-code') || '';
                    var name = transferBtn.getAttribute('data-name') || '';
                    var dpbv = transferBtn.getAttribute('data-dpbv') || '0.00';

                    if (transferSourceId) transferSourceId.value = sourceId;
                    if (transferCode) transferCode.value = code;
                    if (transferCodeDisplay) transferCodeDisplay.value = code;
                    if (transferName) transferName.value = name;
                    if (transferNameDisplay) transferNameDisplay.value = name;
                    if (transferAmount) {
                        transferAmount.value = dpbv;
                        transferAmount.max = dpbv;
                    }
                    if (transferRecipientEmail) transferRecipientEmail.value = '';
                    if (transferRecipientFeedback) {
                        transferRecipientFeedback.textContent = '';
                        transferRecipientFeedback.className = 'd-block mt-1 text-muted';
                    }
                    if (transferMaxText) transferMaxText.textContent = dpbv;
                    transferModal.show();
                    return;
                }

                var link = e.target.closest('.pagination a');
                if (!link) return;
                e.preventDefault();
                fetchResults(link.href);
            });

            function validateRecipientEmail() {
                if (!transferRecipientEmail || !transferRecipientFeedback) return;
                var email = (transferRecipientEmail.value || '').trim();
                if (!email) {
                    transferRecipientFeedback.textContent = '';
                    transferRecipientFeedback.className = 'd-block mt-1 text-muted';
                    return;
                }

                var endpoint = "{{ route('dpbv.check-recipient-email') }}" + '?email=' + encodeURIComponent(email);
                fetch(endpoint, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(function(response) {
                    if (!response.ok) throw new Error('Request failed');
                    return response.json();
                })
                .then(function(data) {
                    if (data && data.exists) {
                        transferRecipientFeedback.textContent = 'Found: ' + (data.name || '') + ' (' + (data.email || email) + ')';
                        transferRecipientFeedback.className = 'd-block mt-1 text-success';
                    } else {
                        transferRecipientFeedback.textContent = (data && data.message) ? data.message : 'Recipient email not found.';
                        transferRecipientFeedback.className = 'd-block mt-1 text-danger';
                    }
                })
                .catch(function() {
                    transferRecipientFeedback.textContent = 'Unable to verify recipient now. Try again.';
                    transferRecipientFeedback.className = 'd-block mt-1 text-warning';
                });
            }

            if (transferRecipientEmail) {
                transferRecipientEmail.addEventListener('input', function() {
                    clearTimeout(recipientTimer);
                    recipientTimer = setTimeout(validateRecipientEmail, 250);
                });
                transferRecipientEmail.addEventListener('blur', validateRecipientEmail);
            }
        })();
    </script>
    @include('partials.pwa-scripts')
</body>
</html>
