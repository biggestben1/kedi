@extends('layouts.admin')

@section('title', 'Create Announcement')

@section('content')
    <div class="page-header">
        <h1 class="page-title">Create Announcement</h1>
        <div>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.announcements.index') }}">Announcements</a></li>
                <li class="breadcrumb-item active" aria-current="page">Create</li>
            </ol>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">New Announcement</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.announcements.store') }}" class="form-vertical">
                @csrf

                <div class="mb-3">
                    <label class="form-label">Title *</label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
                    @error('title')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Content *</label>
                    <textarea name="content" class="form-control" rows="8" required>{{ old('content') }}</textarea>
                    @error('content')<div class="text-danger small">{{ $message }}</div>@enderror
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fe fe-save me-2"></i>Create Announcement
                    </button>
                    <a href="{{ route('admin.announcements.index') }}" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
