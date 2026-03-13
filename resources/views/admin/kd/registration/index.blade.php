@extends('layouts.admin')

@section('title', 'KD Registrations')

@section('content')
    <div class="page-header">
        <h1 class="page-title">KD Registrations</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.kd.index') }}">Borrow</a></li>
                <li class="breadcrumb-item active" aria-current="page">KD Registrations</li>
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
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <h3 class="card-title mb-0">All KD Registrations</h3>
                <form method="GET" class="d-flex gap-2 align-items-center">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search KD NO, name, phone..." value="{{ $search ?? '' }}" style="width:250px">
                    <button type="submit" class="btn btn-sm btn-outline-secondary">Search</button>
                    @if($search ?? '')
                        <a href="{{ route('admin.kd.registration.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                    @endif
                </form>
            </div>
            <a href="{{ route('admin.kd.registration.create') }}" class="btn btn-primary">
                <i class="fe fe-plus me-2"></i>New Registration
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>KD NO</th>
                            <th>Full Name</th>
                            <th>Gender</th>
                            <th>State</th>
                            <th>Phone</th>
                            <th>Sponsor</th>
                            <th>Registration Date</th>
                            <th>Registered By</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($registrations as $reg)
                            <tr>
                                <td><strong>{{ $reg->kd_no }}</strong></td>
                                <td>{{ $reg->full_name }}</td>
                                <td>{{ $reg->gender }}</td>
                                <td>{{ $reg->state }}</td>
                                <td>{{ $reg->phone_number }}</td>
                                <td>
                                    <small>{{ $reg->sponsor_kd_no }}</small><br>
                                    <small class="text-muted">{{ $reg->sponsor_name }}</small>
                                </td>
                                <td>{{ $reg->registration_date->format('Y-m-d') }}</td>
                                <td>
                                    @if($reg->registeredBy)
                                        {{ $reg->registeredBy->name }}
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.kd.registration.show', $reg) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    <a href="{{ route('admin.kd.registration.edit', $reg) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                    <form action="{{ route('admin.kd.registration.destroy', $reg) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this registration?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center text-muted p-4">No registrations found. <a href="{{ route('admin.kd.registration.create') }}">Create one</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($registrations->hasPages())
            <div class="card-footer">{{ $registrations->links() }}</div>
        @endif
    </div>
@endsection
