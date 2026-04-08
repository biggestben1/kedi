@extends('layouts.admin')

@section('content')
<div class="page-header">
    <h1 class="page-title">Trashed Users (Recycle Bin)</h1>
    <div>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.users.index') }}">Users</a></li>
            <li class="breadcrumb-item active" aria-current="page">Trash</li>
        </ol>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Deleted Users</h3>
                <div class="card-options">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-sm">Back to Users</a>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered text-nowrap border-bottom">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Deleted At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($users as $user)
                                <tr>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->role?->display_name ?? '—' }}</td>
                                    <td>{{ $user->deleted_at->format('M d, Y H:i') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.users.restore', $user->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm btn-outline-success">Restore</button>
                                        </form>
                                        <form action="{{ route('admin.users.force-delete', $user->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete this user? This cannot be undone.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Permanent Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted p-4">Trash bin is empty.</td>
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
    </div>
</div>
@endsection
