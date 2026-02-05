@extends('layouts.admin')

@section('title', 'Suppliers')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Suppliers</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Suppliers</li>
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
            <h3 class="card-title mb-0">Suppliers</h3>
            <a href="{{ route('admin.suppliers.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>Add Supplier</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($suppliers as $s)
                            <tr>
                                <td>{{ $s->name }}</td>
                                <td>{{ $s->email ?? '—' }}</td>
                                <td>{{ $s->phone ?? '—' }}</td>
                                <td>{{ Str::limit($s->address, 40) ?? '—' }}</td>
                                <td><a href="{{ route('admin.suppliers.edit', $s) }}" class="btn btn-sm btn-outline-primary">Edit</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No suppliers. <a href="{{ route('admin.suppliers.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
