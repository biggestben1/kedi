@extends('layouts.public-simple')

@section('title', $questionnaire->title)

@section('content')
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 mb-2">{{ $questionnaire->title }}</h1>
            @if($questionnaire->description)
                <p class="text-muted small mb-4">{{ $questionnaire->description }}</p>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0 small">
                        @foreach($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if($response)
                <div class="alert alert-success mb-4">
                    Thank you — your answers have been recorded.
                </div>

                @if($score['total'] > 0)
                    @php $pct = round(100 * $score['correct'] / $score['total']); @endphp
                    <div class="alert alert-info mb-4">
                        <strong>You got {{ $pct }}% correct</strong>
                        <span class="d-block mt-1 text-muted small">
                            ({{ $score['correct'] }} out of {{ $score['total'] }} multiple-choice question{{ $score['total'] !== 1 ? 's' : '' }})
                        </span>
                    </div>
                @endif

                <h2 class="h6 mb-3">Your answers &amp; correct answers</h2>
                @foreach($questionnaire->questions as $q)
                    @php
                        $userAnswer = $response->answers->firstWhere('questionnaire_question_id', $q->id);
                        $letters = range('A', 'Z');
                    @endphp
                    <div class="mb-4 pb-3 border-bottom">
                        <div class="fw-semibold mb-2">{{ $loop->iteration }}. {{ $q->body }}</div>

                        @if($q->type === \App\Models\QuestionnaireQuestion::TYPE_TEXT)
                            <div class="small text-muted mb-1">Your answer</div>
                            <div class="p-2 bg-light rounded" style="white-space: pre-wrap;">{{ $userAnswer?->answer_text ?? '—' }}</div>
                        @else
                            @php
                                $correctOption = $q->options->firstWhere('is_correct', true);
                                $pickedId = $userAnswer?->questionnaire_question_option_id;
                                $pickedCorrect = $correctOption && $pickedId && (int) $pickedId === (int) $correctOption->id;
                                $pickedIndex = $pickedId ? $q->options->values()->search(fn ($o) => (int) $o->id === (int) $pickedId) : false;
                                $correctIndex = $correctOption ? $q->options->values()->search(fn ($o) => (int) $o->id === (int) $correctOption->id) : false;
                            @endphp
                            <div class="d-flex flex-column gap-2">
                                @foreach($q->options as $opt)
                                    @php
                                        $letter = $letters[$loop->index] ?? (string) ($loop->iteration);
                                        $isPicked = $pickedId && (int) $pickedId === (int) $opt->id;
                                        $isCorrectOption = $correctOption && (int) $opt->id === (int) $correctOption->id;
                                    @endphp
                                    <div class="d-flex align-items-start gap-2 p-2 rounded border {{ $isCorrectOption ? 'border-success bg-success-subtle' : ($isPicked && ! $isCorrectOption ? 'border-danger bg-danger-subtle' : '') }}">
                                        <span class="fw-bold small" style="min-width:1.5rem">{{ $letter }}.</span>
                                        <div class="flex-grow-1">
                                            <span>{{ $opt->label }}</span>
                                            @if($isPicked)
                                                <span class="badge bg-secondary ms-1">Your answer</span>
                                            @endif
                                            @if($isCorrectOption)
                                                <span class="badge bg-success ms-1">Correct answer</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($correctOption)
                                <p class="small mb-0 mt-2 {{ $pickedCorrect ? 'text-success' : 'text-danger' }}">
                                    @if($pickedCorrect)
                                        Correct — you chose {{ $pickedIndex !== false ? $letters[$pickedIndex] : '?' }}.
                                    @else
                                        Incorrect — the correct answer is
                                        <strong>{{ $correctIndex !== false ? $letters[$correctIndex] : '?' }}. {{ $correctOption->label }}</strong>.
                                    @endif
                                </p>
                            @endif
                        @endif
                    </div>
                @endforeach

                <a href="{{ route('questionnaires.index') }}" class="btn btn-primary">More questionnaires</a>
                <a href="{{ url('/') }}" class="btn btn-outline-secondary ms-2">Home</a>
            @else
                <form method="POST" action="{{ route('questionnaires.store', $questionnaire->slug) }}">
                    @csrf
                    @guest
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Your name <span class="text-muted">(optional)</span></label>
                                <input type="text" name="respondent_name" class="form-control" value="{{ old('respondent_name') }}" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email <span class="text-muted">(optional)</span></label>
                                <input type="email" name="respondent_email" class="form-control" value="{{ old('respondent_email') }}" maxlength="255">
                            </div>
                        </div>
                    @endguest

                    @foreach($questionnaire->questions as $q)
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="fw-semibold mb-2">{{ $loop->iteration }}. {{ $q->body }}</div>
                            @if($q->type === \App\Models\QuestionnaireQuestion::TYPE_TEXT)
                                <textarea name="answers[{{ $q->id }}]" class="form-control" rows="3" required>{{ old('answers.'.$q->id) }}</textarea>
                            @else
                                <div class="d-flex flex-column gap-2">
                                    @foreach($q->options as $opt)
                                        @php $letter = range('A', 'Z')[$loop->index] ?? (string) ($loop->iteration); @endphp
                                        <label class="d-flex align-items-center gap-2 mb-0">
                                            <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt->id }}" {{ (string) old('answers.'.$q->id) === (string) $opt->id ? 'checked' : '' }} required>
                                            <span><strong>{{ $letter }}.</strong> {{ $opt->label }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach

                    <button type="submit" class="btn btn-primary">Submit</button>
                    <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary ms-2">Back to list</a>
                </form>
            @endif
        </div>
    </div>
@endsection
