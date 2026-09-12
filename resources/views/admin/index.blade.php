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
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark">
                    <h3 class="card-title mb-0">Go Live</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Clear all test <strong>orders</strong>, <strong>invoices</strong>, back orders, and wallet history before going live.
                    </p>
                    <a href="{{ route('admin.system.go-live') }}" class="btn btn-warning">
                        <i class="fe fe-rocket me-2"></i>Go Live — Clear Test Data
                    </a>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection
