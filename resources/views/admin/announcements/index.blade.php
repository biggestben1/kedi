@extends('layouts.admin')

@section('title', 'Announcements')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Announcements</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active" aria-current="page">Announcements</li>
            </ol>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="page-wrapper">
        <div class="container-xl">
            <div class="row row-deck row-cards">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="card-title">All Announcements</div>
                            <div class="card-toolbar">
                                <a href="{{ route('admin.announcements.create') }}" class="btn btn-primary">
                                    <i class="fe fe-plus me-2"></i>New Announcement
                                </a>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter">
                                <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Created By</th>
                                    <th>Published</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse($announcements as $announcement)
                                    <tr>
                                        <td>
                                            <div class="text-truncate">
                                                <strong>{{ $announcement->title }}</strong>
                                            </div>
                                        </td>
                                        <td>{{ $announcement->createdBy?->name ?? 'N/A' }}</td>
                                        <td>{{ $announcement->published_at?->format('M d, Y H:i') }}</td>
                                        <td>
                                            <span class="badge {{ $announcement->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $announcement->is_active ? 'Active' : 'Inactive' }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="{{ route('admin.announcements.edit', $announcement) }}" class="btn btn-sm btn-outline-primary">
                                                    <i class="fe fe-edit"></i>
                                                </a>
                                                <form method="POST" action="{{ route('admin.announcements.toggle-active', $announcement) }}" style="display: inline;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="btn btn-sm {{ $announcement->is_active ? 'btn-outline-warning' : 'btn-outline-success' }}">
                                                        <i class="fe {{ $announcement->is_active ? 'fe-eye-off' : 'fe-eye' }}"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.announcements.destroy', $announcement) }}" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="fe fe-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            No announcements found
                                        </td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($announcements->hasPages())
                            <div class="card-footer">
                                {{ $announcements->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
