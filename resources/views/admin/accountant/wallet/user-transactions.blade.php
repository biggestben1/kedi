@extends('layouts.admin')

@section('title', 'User Wallet Transactions')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Wallet Transactions - {{ $user->name }}</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.accountant.wallet.index') }}">Wallet Management</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.accountant.wallet.users') }}">User Balances</a></li>
                <li class="breadcrumb-item active" aria-current="page">{{ $user->name }}</li>
            </ol>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Name</p>
                    <h5 class="mb-0">{{ $user->name }}</h5>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Email</p>
                    <h5 class="mb-0">{{ $user->email }}</h5>
                </div>
                <div class="col-md-4">
                    <p class="mb-1 text-muted">Current Wallet Balance</p>
                    <h5 class="mb-0 text-success">₦{{ number_format($user->wallet_balance ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Transaction History</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
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
                                @elseif($tx->status === 'rejected')
                                    <span class="badge bg-danger">Rejected</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($tx->status) }}</span>
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
                            <td colspan="6" class="text-center text-muted p-4">No transactions found for this user.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($transactions->hasPages())
                <div class="mt-3">{{ $transactions->links() }}</div>
            @endif

            <div class="mt-3">
                <a href="{{ route('admin.accountant.wallet.users') }}" class="btn btn-outline-secondary">
                    <i class="fe fe-arrow-left me-1"></i>Back to User Balances
                </a>
            </div>
        </div>
    </div>
@endsection
