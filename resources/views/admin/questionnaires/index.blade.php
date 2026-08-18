@extends('layouts.admin')

@section('title', 'Questionnaires')

@section('content')
    <div class="page-header d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div>
            <h1 class="page-title">Questionnaires</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin') }}">Admin</a></li>
                <li class="breadcrumb-item active">Questionnaires</li>
            </ol>
        </div>
        <a href="{{ route('admin.questionnaires.create') }}" class="btn btn-primary"><i class="fe fe-plus me-1"></i>New questionnaire</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <form method="GET" class="row g-2 align-items-end mb-3">
                <div class="col-md-6">
                    <label class="form-label">Search</label>
                    <input type="text" name="q" class="form-control" value="{{ $q }}" placeholder="Title or slug">
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Public link</th>
                            <th>Active</th>
                            <th class="text-end">Responses</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($questionnaires as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td>
                                    <a href="{{ route('questionnaires.show', $item->slug) }}" class="small" target="_blank" rel="noopener">{{ route('questionnaires.show', $item->slug) }}</a>
                                </td>
                                <td>{{ $item->is_active ? 'Yes' : 'No' }}</td>
                                <td class="text-end">{{ $item->responses_count }}</td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('questionnaires.show', $item->slug) }}" class="btn btn-sm btn-outline-info" target="_blank" rel="noopener">Preview</a>
                                    <a href="{{ route('admin.questionnaires.responses', $item) }}" class="btn btn-sm btn-outline-secondary">Responses</a>
                                    <a href="{{ route('admin.questionnaires.edit', $item) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                                    <form action="{{ route('admin.questionnaires.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this questionnaire and all responses?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-muted text-center py-4">No questionnaires yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($questionnaires->hasPages())
                <div class="mt-3">{{ $questionnaires->links() }}</div>
            @endif
        </div>
    </div>
@endsection
