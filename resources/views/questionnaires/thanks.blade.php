@extends('layouts.public-simple')

@section('title', 'Thank you')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <h1 class="h4 mb-3">Thank you</h1>
            <p class="text-muted mb-4">Your answers for <strong>{{ $questionnaire->title }}</strong> have been recorded.</p>
            <a href="{{ route('questionnaires.show', $questionnaire->slug) }}" class="btn btn-primary">View your answers</a>
            <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary ms-2">More questionnaires</a>
        </div>
    </div>
@endsection
