@extends('layouts.admin')

@section('title', 'Users')

@section('content')
    <div class="page-header">
        <h1 class="page-title">
            @if(isset($createdByUser) && $createdByUser && auth()->user()->role?->name === 'reseller')
                Customers of {{ $createdByUser->name }}
            @elseif(isset($createdByUser) && $createdByUser && auth()->user()->role?->name === 'headquarters')
                My Users @if(!empty($roleFilter)) – {{ ucfirst(str_replace('_', ' ', $roleFilter)) }}@endif
            @elseif(request('view_admin') === '1' && auth()->user()->role?->name === 'branch')
                My Admin
            @elseif(isset($createdByUser) && $createdByUser)
                Customers of {{ $createdByUser->name }}
            @elseif(!empty($roleFilter))
                Users – {{ ucfirst($roleFilter) }}
            @else
                Users
            @endif
        </h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
                @if(isset($createdByUser) && $createdByUser && auth()->user()->role?->name === 'headquarters')
                    <li class="breadcrumb-item active" aria-current="page">My Users @if(!empty($roleFilter))– {{ ucfirst(str_replace('_', ' ', $roleFilter)) }}@endif</li>
                @elseif(isset($createdByUser) && $createdByUser)
                    <li class="breadcrumb-item"><a href="{{ route('admin.users.index', ['role' => 'reseller']) }}">Resellers</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Customers of {{ $createdByUser->name }}</li>
                @elseif(!empty($roleFilter))
                    <li class="breadcrumb-item active" aria-current="page">{{ ucfirst($roleFilter) }}</li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">Users</li>
                @endif
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
        <div class="card-header">
            <div class="d-flex flex-wrap gap-2 align-items-center w-100">
                @if(auth()->user()->isSuperAdmin() && !isset($createdByUser))
                <div class="d-flex gap-1 flex-wrap me-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm {{ empty($roleFilter) ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
                    <a href="{{ route('admin.users.index', ['role' => 'super_admin']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'super_admin' ? 'btn-primary' : 'btn-outline-secondary' }}">Super Admin</a>
                    <a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'accountant' ? 'btn-primary' : 'btn-outline-secondary' }}">Accountant</a>
                    <a href="{{ route('admin.users.index', ['role' => 'dispatch']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'dispatch' ? 'btn-primary' : 'btn-outline-secondary' }}">Dispatch</a>
                    <a href="{{ route('admin.users.index', ['role' => 'headquarters']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'headquarters' ? 'btn-primary' : 'btn-outline-secondary' }}">Headquarters</a>
                    <a href="{{ route('admin.users.index', ['role' => 'branch']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'branch' ? 'btn-primary' : 'btn-outline-secondary' }}">Branch</a>
                    <a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'annex' ? 'btn-primary' : 'btn-outline-secondary' }}">Annex</a>
                    <a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'service_center' ? 'btn-primary' : 'btn-outline-secondary' }}">Service Center</a>
                </div>
                @elseif(auth()->user()->role?->name === 'headquarters')
                <div class="d-flex gap-1 flex-wrap me-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm {{ empty($roleFilter) ? 'btn-primary' : 'btn-outline-secondary' }}">All</a>
                    <a href="{{ route('admin.users.index', ['role' => 'branch']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'branch' ? 'btn-primary' : 'btn-outline-secondary' }}">Branch</a>
                    <a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'annex' ? 'btn-primary' : 'btn-outline-secondary' }}">Annex</a>
                    <a href="{{ route('admin.users.index', ['role' => 'service_center']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'service_center' ? 'btn-primary' : 'btn-outline-secondary' }}">Service Center</a>
                    <a href="{{ route('admin.users.index', ['role' => 'accountant']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'accountant' ? 'btn-primary' : 'btn-outline-secondary' }}">Accountant</a>
                </div>
                @elseif(auth()->user()->role?->name === 'branch')
                <div class="d-flex gap-1 flex-wrap me-2">
                    <a href="{{ route('admin.users.index', ['role' => 'annex']) }}" class="btn btn-sm {{ ($roleFilter ?? '') === 'annex' && request('view_admin') !== '1' ? 'btn-primary' : 'btn-outline-secondary' }}">My Annex Users</a>
                    @if(auth()->user()->created_by_user_id)
                        <a href="{{ route('admin.users.index', ['view_admin' => '1']) }}" class="btn btn-sm {{ request('view_admin') === '1' ? 'btn-primary' : 'btn-outline-secondary' }}">My Admin</a>
                    @endif
                </div>
                @endif
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2 flex-grow-1">
                    @if(isset($roleFilter) && $roleFilter)
                        <input type="hidden" name="role" value="{{ $roleFilter }}">
                    @endif
                    @if(request('view_admin') === '1')
                        <input type="hidden" name="view_admin" value="1">
                    @endif
                    <input type="search" name="q" class="form-control" placeholder="Search name, email, phone..." value="{{ $q }}">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    @if($q || !empty($roleFilter))
                        <a class="btn btn-outline-secondary" href="{{ route('admin.users.index', array_filter(request()->only('role', 'view_admin'))) }}">Clear search</a>
                    @endif
                </form>
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary ms-auto">
                    <i class="fe fe-user-plus me-2"></i>@if(auth()->user()->role?->name === 'reseller')Create Customer@else Create User @endif
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th class="text-end">DPBV</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone ?? '—' }}</td>
                                <td>{{ $user->role?->display_name ?? '—' }}</td>
                                <td class="text-end">{{ $user->dpbv_collections_sum_dpbv ? number_format($user->dpbv_collections_sum_dpbv, 2) : '—' }}</td>
                                <td class="text-end">
                                    @if(isset($roleFilter) && $roleFilter === 'reseller')
                                        <a href="{{ route('admin.users.index', ['role' => 'customer', 'created_by' => $user->id]) }}" class="btn btn-sm btn-outline-info">Customers</a>
                                        <a href="{{ route('admin.invoices.create', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-success">Create Invoice</a>
                                    @endif
                                    @if(isset($createdByUser) && $createdByUser)
                                        <a href="{{ route('admin.invoices.create', ['user_id' => $user->id]) }}" class="btn btn-sm btn-outline-success">Create Invoice</a>
                                    @endif
                                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ auth()->user()->role?->name === 'reseller' ? 6 : 5 }}" class="text-center text-muted p-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($users->hasPages())
            <div class="card-footer">
                {{ $users->links() }}
            </div>
        @endif
    </div>
@endsection
