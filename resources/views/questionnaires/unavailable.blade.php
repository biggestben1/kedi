@extends('layouts.public-simple')

@section('title', $questionnaire->title)

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4 text-center">
            <h1 class="h4 mb-3">{{ $questionnaire->title }}</h1>
            <p class="text-muted mb-4">This questionnaire is not published yet and cannot be taken at this time.</p>
            <a href="{{ route('questionnaires.index') }}" class="btn btn-primary">Back to questionnaires</a>
        </div>
    </div>
@endsection
