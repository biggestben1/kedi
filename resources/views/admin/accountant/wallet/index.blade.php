@extends('layouts.admin')

@section('title', $ownAccountOnly ?? false ? 'My Wallet' : 'Wallet Management - All Transactions')

@section('content')
    <div class="page-header">
        <h1 class="page-title">{{ ($ownAccountOnly ?? false) ? 'My Wallet' : 'Wallet Management - All Transactions' }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.accountant.wallet.index') }}">Wallet Management</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ ($ownAccountOnly ?? false) ? 'My Transactions' : 'All Transactions' }}</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-primary text-white me-3">
                            <i class="fe fe-list"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Total Transactions</p>
                            <h3 class="mb-0">{{ number_format($stats['total_transactions']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-warning text-white me-3">
                            <i class="fe fe-clock"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Pending</p>
                            <h3 class="mb-0">{{ number_format($stats['total_pending']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-success text-white me-3">
                            <i class="fe fe-check-circle"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Approved</p>
                            <h3 class="mb-0">{{ number_format($stats['total_approved']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="avatar-icon bg-info text-white me-3">
                            <i class="fe fe-dollar-sign"></i>
                        </div>
                        <div>
                            <p class="mb-1 text-muted">Total Credits</p>
                            <h3 class="mb-0">₦{{ number_format($stats['total_credits'], 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ ($ownAccountOnly ?? false) ? 'My Transactions' : 'All Wallet Transactions' }}</h3>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <form method="GET" action="{{ route('admin.accountant.wallet.index') }}" class="mb-4">
                <div class="row g-3">
                    @if(!($ownAccountOnly ?? false))
                    <div class="col-md-3">
                        <label class="form-label">Search User</label>
                        <input type="text" name="search" class="form-control" value="{{ $search }}" placeholder="Name or email">
                    </div>
                    @endif
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <option value="">All</option>
                            <option value="pending" {{ $statusFilter === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="accepted" {{ $statusFilter === 'accepted' ? 'selected' : '' }}>Approved</option>
                            <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Type</label>
                        <select name="type" class="form-select">
                            <option value="">All</option>
                            <option value="credit" {{ $typeFilter === 'credit' ? 'selected' : '' }}>Credit</option>
                            <option value="debit" {{ $typeFilter === 'debit' ? 'selected' : '' }}>Debit</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date From</label>
                        <input type="date" name="date_from" class="form-control" value="{{ $dateFrom }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Date To</label>
                        <input type="date" name="date_to" class="form-control" value="{{ $dateTo }}">
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">&nbsp;</label>
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            @if(!($ownAccountOnly ?? false))
                            <th>User</th>
                            @endif
                            <th>Type</th>
                            <th class="text-end">Amount</th>
                            <th>Status</th>
                            <th>Reference</th>
                            <th class="text-end">Balance After</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $tx)
                        <tr>
                            <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                            @if(!($ownAccountOnly ?? false))
                            <td>
                                <strong>{{ $tx->user?->name }}</strong><br>
                                <small class="text-muted">{{ $tx->user?->email }}</small>
                            </td>
                            @endif
                            <td>
                                @if($tx->type === 'credit')
                                    <span class="badge bg-success">Credit</span>
                                @else
                                    <span class="badge bg-danger">Debit</span>
                                @endif
                            </td>
                            <td class="text-end">
                                @if($tx->type === 'credit')
                                    <span class="text-success">+₦{{ number_format($tx->amount, 2) }}</span>
                                @else
                                    <span class="text-danger">-₦{{ number_format($tx->amount, 2) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($tx->status === 'pending')
                                    <span class="badge bg-warning">Pending</span>
                                @elseif($tx->status === 'accepted')
                                    <span class="badge bg-success">Approved</span>
                                @else
                                    <span class="badge bg-danger">Rejected</span>
                                @endif
                            </td>
                            <td>{{ $tx->reference ?? '—' }}</td>
                            <td class="text-end">
                                @if($tx->balance_after !== null)
                                    ₦{{ number_format($tx->balance_after, 2) }}
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ ($ownAccountOnly ?? false) ? 6 : 7 }}" class="text-center text-muted p-4">No transactions found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="mt-3">{{ $transactions->links() }}</div>
            @endif
        </div>
    </div>
@endsection
