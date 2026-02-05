@extends('layouts.admin')

@section('title', 'Wallet Management - User Balances')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Wallet Management - User Balances</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.accountant.wallet.index') }}">Wallet Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">User Balances</li>
            </ol>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-primary text-white me-3">
                            <i class="fe fe-users"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Users with Balance</p>
                            <h3 class="mb-0">{{ number_format($stats['total_users']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-success text-white me-3">
                            <i class="fe fe-dollar-sign"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Total System Balance</p>
                            <h3 class="mb-0">₦{{ number_format($stats['total_balance'], 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">User Wallet Balances</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.accountant.wallet.users') }}" class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Search User</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Name or email">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="customer" {{ $roleFilter === 'customer' ? 'selected' : '' }}>Customer</option>
                            <option value="reseller" {{ $roleFilter === 'reseller' ? 'selected' : '' }}>Reseller</option>
                            <option value="wholesale_staff" {{ $roleFilter === 'wholesale_staff' ? 'selected' : '' }}>Wholesale Staff</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th class="text-end">Wallet Balance</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                        <tr>
                            <td>
                                <strong>{{ $user->name }}</strong>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ $user->role?->display_name ?? '—' }}</span>
                            </td>
                            <td>{{ $user->email }}</td>
                            <td class="text-end">
                                <strong class="text-success">₦{{ number_format($user->wallet_balance ?? 0, 2) }}</strong>
                            </td>
                            <td class="text-end">
                                <a href="{{ route('admin.accountant.wallet.user-transactions', $user) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="fe fe-list me-1"></i>View Transactions
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted p-4">No users with wallet balance found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="mt-3">{{ $users->links() }}</div>
            @endif
        </div>
    </div>
@endsection
