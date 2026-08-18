@extends('layouts.admin')

@section('title', 'Responses: '.$questionnaire->title)

@section('content')
    <div class="page-header d-flex flex-wrap justify-content-between gap-2">
        <div>
            <h1 class="page-title">Responses</h1>
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.questionnaires.index') }}">Questionnaires</a></li>
                <li class="breadcrumb-item active">{{ $questionnaire->title }}</li>
            </ol>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('questionnaires.show', $questionnaire->slug) }}" class="btn btn-outline-info btn-sm" target="_blank" rel="noopener">Preview</a>
            <a href="{{ route('admin.questionnaires.edit', $questionnaire) }}" class="btn btn-outline-primary btn-sm">Edit</a>
        </div>
    </div>

    <p class="text-muted small">Total responses: {{ $questionnaire->responses_count }}</p>

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>When</th>
                            <th>User</th>
                            <th>Name / Email</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($responses as $r)
                            <tr>
                                <td>{{ $r->id }}</td>
                                <td>{{ $r->created_at->format('M d, Y H:i') }}</td>
                                <td>{{ $r->user?->name ?? '—' }}</td>
                                <td>
                                    @if($r->respondent_name || $r->respondent_email)
                                        {{ $r->respondent_name }} @if($r->respondent_email)<br><small class="text-muted">{{ $r->respondent_email }}</small>@endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.questionnaires.responses.show', [$questionnaire, $r]) }}" class="btn btn-sm btn-outline-primary">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No responses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($responses->hasPages())
            <div class="card-footer">{{ $responses->links() }}</div>
        @endif
    </div>
@endsection
