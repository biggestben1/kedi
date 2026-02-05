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
        <div class="col-lg-6">
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
        <div class="col-lg-6">
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
    </div>
@endsection
