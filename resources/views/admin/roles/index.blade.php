@extends('layouts.admin')

@section('title', 'Roles')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Roles</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Roles</li>
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
                <form method="GET" action="{{ route('admin.roles.index') }}" class="d-flex gap-2 flex-grow-1">
                    <input type="search" name="q" class="form-control" placeholder="Search name or display name..." value="{{ $q }}">
                    <button class="btn btn-outline-primary" type="submit">Search</button>
                    @if($q)
                        <a class="btn btn-outline-secondary" href="{{ route('admin.roles.index') }}">Clear</a>
                    @endif
                </form>
                <a href="{{ route('admin.roles.create') }}" class="btn btn-primary ms-auto">
                    <i class="fe fe-plus me-2"></i>Create Role
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Display Name</th>
                            <th>Description</th>
                            <th class="text-end">Users</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($roles as $role)
                            <tr>
                                <td class="align-middle"><code>{{ $role->name }}</code></td>
                                <td class="align-middle">{{ $role->display_name }}</td>
                                <td class="align-middle">{{ Str::limit($role->description, 60) ?? '—' }}</td>
                                <td class="text-end align-middle">{{ $role->users_count }}</td>
                                <td class="text-end align-middle">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    @if($role->name !== \App\Models\Role::SUPER_ADMIN)
                                        <form action="{{ route('admin.roles.destroy', $role) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this role?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted p-4">No roles found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($roles->hasPages())
            <div class="card-footer">
                {{ $roles->links() }}
            </div>
        @endif
    </div>
@endsection
