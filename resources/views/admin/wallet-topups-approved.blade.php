@extends('layouts.admin')

@section('title', 'Wallet Top-ups – Approved')

@section('content')
    <div class="page-header">
        <h1 class="page-title">All Approved Wallet Top-ups</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.wallet_topups') }}">Wallet Top-ups</a></li>
                <li class="breadcrumb-item active" aria-current="page">Approved</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            @if($transactions->isEmpty())
                <p class="text-muted p-4 mb-0">No approved top-ups.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Approved at</th>
                                <th>User</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">Balance after</th>
                                <th>Proof</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $tx)
                            <tr>
                                <td>{{ $tx->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $tx->approved_at?->format('M d, Y H:i') }}</td>
                                <td>
                                    <strong>{{ $tx->user?->name }}</strong><br>
                                    <small class="text-muted">{{ $tx->user?->email }}</small>
                                </td>
                                <td class="text-end">₦{{ number_format($tx->amount, 2) }}</td>
                                <td class="text-end">₦{{ $tx->balance_after !== null ? number_format($tx->balance_after, 2) : '—' }}</td>
                                <td>
                                    @if($tx->proof_path)
                                        <a href="{{ url('api/v1/storage/' . $tx->proof_path) }}" target="_blank">View proof</a>
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
@endsection
