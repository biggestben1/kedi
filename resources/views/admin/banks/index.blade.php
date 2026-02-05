@extends('layouts.admin')

@section('title', 'Banks')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Banks</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Banks</li>
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
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Banks</h3>
            <a href="{{ route('admin.banks.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>Add Bank</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($banks as $bank)
                            <tr>
                                <td>{{ $bank->name }}</td>
                                <td>{{ $bank->account_name ?? '—' }}</td>
                                <td>{{ $bank->account_number ?? '—' }}</td>
                                <td>
                                    @if($bank->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Deactivated</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.banks.edit', $bank) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.banks.destroy', $bank) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this bank?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted p-4">No banks. <a href="{{ route('admin.banks.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
