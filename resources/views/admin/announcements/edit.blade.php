@extends('layouts.admin')

@section('title', 'Edit Announcement')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Edit Announcement</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Announcements</a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $announcement->title }}</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}" class="form-vertical">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title', $announcement->title) }}" required>
                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="8" required>{{ old('content', $announcement->content) }}</textarea>
                    @error('content')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-save me-2"></i>Update Announcement
                    </button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
