@extends('layouts.admin')

@section('title', 'Warehouses')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Warehouses</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Warehouses</li>
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Warehouses</h3>
            <a href="{{ route('admin.warehouses.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>Add Warehouse</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th class="text-end">Products</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warehouses as $w)
                            <tr>
                                <td>{{ $w->name }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.products.index', ['warehouse_id' => $w->id]) }}" class="text-decoration-none">
                                        {{ (int) ($w->products_count ?? 0) }}
                                    </a>
                                </td>
                                <td>
                                    @if($w->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.warehouses.edit', $w) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.warehouses.destroy', $w) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this warehouse?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted p-4">No warehouses. <a href="{{ route('admin.warehouses.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

