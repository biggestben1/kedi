@extends('layouts.public-simple')

@section('title', 'Questionnaires')

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-3">Questionnaires</h1>
            <p class="text-muted small mb-4">Pick a questionnaire below to complete it.</p>
            @forelse($questionnaires as $q)
                <div class="d-flex flex-wrap justify-content-between align-items-center border rounded p-3 mb-2 gap-2">
                    <div>
                        <div class="fw-semibold">{{ $q->title }}</div>
                        @if($q->description)
                            <div class="text-muted small mt-1">{{ \Illuminate\Support\Str::limit($q->description, 160) }}</div>
                        @endif
                    </div>
                    <a href="{{ route('questionnaires.show', $q->slug) }}" class="btn btn-primary btn-sm">Start</a>
                </div>
            @empty
                <p class="text-muted mb-0">No questionnaires are available right now.</p>
            @endforelse
        </div>
    </div>
@endsection
