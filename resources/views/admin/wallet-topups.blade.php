@extends('layouts.admin')

@section('title', 'Wallet Top-ups')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Pending Wallet Top-ups (Accountant Approval)</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.wallet_topups') }}">Wallet Top-ups</a></li>
                <li class="breadcrumb-item active" aria-current="page">Pending</li>
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

    <div class="card">
        <div class="card-body p-0">
            @if($pending->isEmpty())
                <p class="text-muted p-4 mb-0">No pending top-ups.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th class="text-end">Amount</th>
                                <th>Proof</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pending as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                <td>
                                    <strong>{{ $tx->user?->name }}</strong><br>
                                    <small class="text-muted">{{ $tx->user?->email }}</small>
                                </td>
                                <td class="text-end">₦{{ number_format($tx->amount, 2) }}</td>
                                <td>
                                    @if($tx->proof_path)
                                        <a href="{{ url('api/v1/storage/' . $tx->proof_path) }}" target="_blank">View proof</a>
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.wallet_topups.approve', $tx) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Accept and credit wallet?')">Accept</button>
                                    </form>
                                    <form action="{{ route('admin.wallet_topups.reject', $tx) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject this top-up?')">Reject</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
