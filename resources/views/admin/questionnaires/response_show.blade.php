@extends('layouts.admin')

@section('title', 'Response #'.$response->id)

@section('content')
    <div class="page-header">
        <h1 class="page-title">Response #{{ $response->id }}</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.questionnaires.index') }}">Questionnaires</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.questionnaires.responses', $questionnaire) }}">{{ $questionnaire->title }}</a></li>
            <li class="breadcrumb-item active">Response</li>
        </ol>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-1"><strong>Questionnaire:</strong> {{ $questionnaire->title }}</p>
            <p class="mb-1 text-muted small">Submitted {{ $response->created_at->format('M d, Y H:i') }}</p>
            @if($response->user)
                <p class="mb-0"><strong>User:</strong> {{ $response->user->name }} ({{ $response->user->email }})</p>
            @endif
            @if($response->respondent_name || $response->respondent_email)
                <p class="mb-0 mt-2"><strong>Guest:</strong> {{ $response->respondent_name }} @if($response->respondent_email) — {{ $response->respondent_email }} @endif</p>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Answers</h3></div>
        <div class="card-body">
            @foreach($response->answers as $ans)
                <div class="mb-4 pb-3 border-bottom">
                    <div class="fw-semibold mb-1">{{ $ans->question?->body ?? 'Question' }}</div>
                    @if($ans->question?->type === \App\Models\QuestionnaireQuestion::TYPE_TEXT)
                        <div class="text-muted" style="white-space: pre-wrap;">{{ $ans->answer_text }}</div>
                    @else
                        <div>{{ $ans->option?->label ?? '—' }}</div>
                    @endif
                </div>
            @endforeach
        </div>
        <div class="card-footer">
            <a href="{{ route('admin.questionnaires.responses', $questionnaire) }}" class="btn btn-outline-secondary">Back to list</a>
        </div>
    </div>
@endsection
