@php
    $pageTitle = 'Order Groups';
@endphp
<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }} – {{ config('app.name') }}</title>
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('images/logo.png') . '?v=3' }}" />
    @include('partials.pwa-head')
    <link href="{{ asset('sash/assets/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/style.css') }}" rel="stylesheet" />
    <link href="{{ asset('sash/assets/css/icons.css') }}" rel="stylesheet" />
    <link id="theme" rel="stylesheet" type="text/css" media="all" href="{{ asset('sash/assets/colors/color1.css') }}" />
</head>
<body class="app sidebar-mini ltr">
<div class="page">
    <div class="page-main">
        <div class="app-header header sticky">
            <div class="container-fluid main-container">
                <div class="d-flex">
                    <a aria-label="Hide Sidebar" class="app-sidebar__toggle" data-bs-toggle="sidebar" href="javascript:void(0)"></a>
                    <div class="main-header-center ms-3 d-none d-lg-block">
                        <a href="{{ route('shop') }}" class="btn btn-outline-primary btn-sm">Back to Shop</a>
                    </div>
                    <div class="d-flex order-lg-2 ms-auto header-right-icons">
                        <a class="nav-link icon" href="{{ route('shop') }}"><i class="fe fe-shopping-cart"></i></a>
                    </div>
                </div>
            </div>
        </div>

        <div class="sticky">
            <div class="app-sidebar__overlay" data-bs-toggle="sidebar"></div>
            <div class="app-sidebar">
                <div class="side-header">
                    <a class="header-brand1" href="{{ route('shop') }}">
                        <img src="{{ asset('images/logo.png') . '?v=3' }}" class="header-brand-img light-logo1" alt="{{ config('app.name') }}">
                    </a>
                </div>
                <div class="main-sidemenu">
                    <ul class="side-menu">
                        <li class="slide"><a class="side-menu__item" href="{{ route('shop') }}"><i class="side-menu__icon fe fe-home"></i><span class="side-menu__label">Shop</span></a></li>
                        <li class="slide"><a class="side-menu__item" href="{{ route('dashboard') }}"><i class="side-menu__icon fe fe-grid"></i><span class="side-menu__label">Dashboard</span></a></li>
                        <li class="slide"><a class="side-menu__item" href="{{ route('orders.index') }}"><i class="side-menu__icon fe fe-package"></i><span class="side-menu__label">My Orders</span></a></li>
                        <li class="slide"><a class="side-menu__item active" href="{{ route('order-groups.index') }}"><i class="side-menu__icon fe fe-layers"></i><span class="side-menu__label">Order Groups</span></a></li>
                        <li class="slide"><a class="side-menu__item" href="{{ route('wallet.index') }}"><i class="side-menu__icon fe fe-dollar-sign"></i><span class="side-menu__label">Wallet</span></a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="main-content app-content mt-0">
            <div class="side-app">
                <div class="main-container container-fluid">
                    <div class="page-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h1 class="page-title mb-1">Order Groups</h1>
                            <p class="text-muted mb-0">Start a session, add many orders, then pay once for the whole group.</p>
                        </div>
                        <a href="{{ route('order-groups.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>Create group</a>
                    </div>

                    @foreach (['success', 'message', 'error'] as $flash)
                        @if(session($flash))
                            <div class="alert alert-{{ $flash === 'error' ? 'danger' : 'success' }} alert-dismissible fade show">
                                {{ session($flash) }}
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        @endif
                    @endforeach

                    @if(($openGroups ?? collect())->isNotEmpty())
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary-transparent">
                            <h3 class="card-title mb-0"><i class="fe fe-layers me-1"></i> Switch session</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">
                                Only one group is active at a time. Click <strong>Activate</strong> to switch.
                                @if($activeGroup ?? null)
                                    Current: <strong>{{ $activeGroup->displayName() }}</strong>
                                @else
                                    No group is active right now.
                                @endif
                            </p>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($openGroups as $openGroup)
                                    @if((int) $activeGroupId === (int) $openGroup->id)
                                        <div class="btn-group" role="group">
                                            <span class="btn btn-success disabled">
                                                <i class="fe fe-check me-1"></i>{{ $openGroup->displayName() }} (active)
                                            </span>
                                            <a href="{{ route('shop') }}" class="btn btn-outline-success">Go to shop</a>
                                        </div>
                                    @else
                                        <form action="{{ route('order-groups.resume', $openGroup) }}" method="POST" class="mb-0">
                                            @csrf
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fe fe-play me-1"></i>Activate {{ $openGroup->displayName() }}
                                            </button>
                                        </form>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="card">
                        <div class="card-body">
                            @if($groups->isEmpty())
                                <p class="mb-3 text-muted">No groups yet. Create one to start a multi-order session.</p>
                                <a href="{{ route('order-groups.create') }}" class="btn btn-primary">Create your first group</a>
                            @else
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Orders</th>
                                            <th>Drafts</th>
                                            <th class="text-end">Total amount</th>
                                            <th>Status</th>
                                            <th>Started</th>
                                            <th>Session</th>
                                            <th></th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        @foreach($groups as $group)
                                            @php
                                                $groupTotal = (float) ($group->total_amount > 0
                                                    ? $group->total_amount
                                                    : ($group->orders_sum_subtotal ?? 0));
                                                $isActive = (int) $activeGroupId === (int) $group->id;
                                            @endphp
                                            <tr class="{{ $isActive ? 'table-success' : '' }}">
                                                <td>
                                                    <a href="{{ route('order-groups.show', $group) }}">{{ $group->displayName() }}</a>
                                                    @if($isActive)
                                                        <span class="badge bg-success ms-1">Active session</span>
                                                    @endif
                                                </td>
                                                <td>{{ $group->orders_count }}</td>
                                                <td>{{ $group->draft_orders_count }}</td>
                                                <td class="text-end text-nowrap">₦{{ number_format($groupTotal, 0) }}</td>
                                                <td><span class="badge bg-{{ $group->status === 'open' ? 'info' : ($group->status === 'paid' ? 'success' : 'secondary') }}">{{ ucfirst($group->status) }}</span></td>
                                                <td>{{ optional($group->started_at)->format('M j, Y H:i') ?? $group->created_at->format('M j, Y H:i') }}</td>
                                                <td class="text-nowrap">
                                                    @if($group->isOpen() && $isActive)
                                                        <span class="badge bg-success">Active</span>
                                                        <a href="{{ route('shop') }}" class="btn btn-sm btn-success ms-1">Shop</a>
                                                    @elseif($group->isOpen())
                                                        <form action="{{ route('order-groups.resume', $group) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-primary">
                                                                <i class="fe fe-play me-1"></i>Activate
                                                            </button>
                                                        </form>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="text-end text-nowrap">
                                                    @if($group->isOpen() && (int) ($group->draft_orders_count ?? 0) > 0)
                                                        <a href="{{ route('order-groups.pay-form', $group) }}" class="btn btn-sm btn-success"><i class="fe fe-credit-card me-1"></i>Pay all</a>
                                                    @endif
                                                    <a href="{{ route('order-groups.show', $group) }}" class="btn btn-sm btn-outline-primary">Open</a>
                                                    <a href="{{ route('order-groups.edit', $group) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                                    <form action="{{ route('order-groups.destroy', $group) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this group? Orders will stay, but leave the group.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="btn btn-sm btn-outline-danger">Delete</button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div class="mt-3">{{ $groups->links() }}</div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('sash/assets/plugins/bootstrap/js/popper.min.js') }}"></script>
<script src="{{ asset('sash/assets/plugins/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('sash/assets/js/jquery.min.js') }}"></script>
<script src="{{ asset('sash/assets/js/sticky.js') }}"></script>
<script src="{{ asset('sash/assets/js/sidemenu.js') }}"></script>
</body>
</html>
