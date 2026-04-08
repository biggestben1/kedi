@extends('layouts.admin')

@section('title', 'Admin')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Admin</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Admin</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Account Management</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Manage users (create, edit, delete) and assign roles.</p>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary">
                        <i class="fe fe-users me-2"></i>Users
                    </a>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Approvals</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Review pending wallet top-ups.</p>
                    <a href="{{ route('admin.wallet_topups') }}" class="btn btn-outline-primary">
                        <i class="fe fe-check-circle me-2"></i>Wallet Top-ups
                    </a>
                </div>
            </div>
        </div>
        @if(auth()->user()->isSuperAdmin())
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">KD Registration</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Register or manage KD numbers linked to user accounts.</p>
                    <a href="{{ route('admin.kd.registration.create') }}" class="btn btn-outline-primary">
                        <i class="fe fe-file-text me-2"></i>New KD Registration
                    </a>
                </div>
            </div>
        </div>
        @endif
        @if(auth()->user()->isSuperAdmin())
        <div class="col-lg-12 mt-4">
            <div class="card border-danger">
                <div class="card-header bg-danger text-white">
                    <h3 class="card-title mb-0">Danger Zone</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        This will delete <strong>ALL orders</strong>, <strong>ALL wallet transactions</strong>, and reset <strong>every user&apos;s wallet balance</strong> to ₦0.00.
                    </p>
                    <form method="POST" action="{{ route('admin.system.clear-orders-wallet') }}" onsubmit="return confirm('This will delete ALL orders and wallet transactions and reset every user\\'s wallet balance to ₦0.00. Are you absolutely sure?');">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            <i class="fe fe-alert-triangle me-2"></i>Clear Orders &amp; Wallet
                        </button>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
