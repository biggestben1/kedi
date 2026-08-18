@extends('layouts.admin')

@section('title', 'POS Machines')

@section('content')
    <div class="page-header">
        <h1 class="page-title">POS Machines</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">POS Machines</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">POS Machines</h3>
            <a href="{{ route('admin.pos-machines.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>Add POS Machine</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Bank Name</th>
                            <th>Account Name</th>
                            <th>Account Number</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($posMachines as $m)
                            <tr>
                                <td>{{ $m->bank_name }}</td>
                                <td>{{ $m->account_name }}</td>
                                <td>{{ $m->account_number }}</td>
                                <td>
                                    @if($m->is_active)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.pos-machines.edit', $m) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.pos-machines.destroy', $m) }}" method="POST" class="d-inline"
                                        onsubmit="return confirm('Delete this record?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted p-4">No POS machines. <a href="{{ route('admin.pos-machines.create') }}">Add one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

