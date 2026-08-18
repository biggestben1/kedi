@php
    $opts = $qrow['options'] ?? ['', ''];
    while (count($opts) < 2) {
        $opts[] = '';
    }
    $type = $qrow['type'] ?? 'single_choice';
    $answerKey = (int) ($qrow['answer_key'] ?? 0);
    $letters = range('A', 'Z');
@endphp
<div class="card mb-3 question-block" data-index="{{ $qi }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <strong>Question <span class="question-num">{{ $qi + 1 }}</span></strong>
            <button type="button" class="btn btn-sm btn-outline-danger remove-question {{ ($showRemove ?? true) ? '' : 'd-none' }}" aria-label="Remove question">Remove</button>
        </div>
        <div class="mb-3">
            <label class="form-label">Question</label>
            <textarea name="questions[{{ $qi }}][body]" class="form-control question-text" rows="2" placeholder="Enter the question" required>{{ $qrow['body'] ?? '' }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Answer type</label>
            <select name="questions[{{ $qi }}][type]" class="form-select form-select-sm q-type" style="width:auto">
                <option value="single_choice" {{ $type === 'single_choice' ? 'selected' : '' }}>Multiple choice</option>
                <option value="text" {{ $type === 'text' ? 'selected' : '' }}>Text answer</option>
            </select>
        </div>
        <div class="mcq-wrap" style="{{ $type === 'single_choice' ? '' : 'display:none' }}">
            <div class="mb-2">
                <label class="form-label small">Options</label>
                <div class="question-options-list" data-question-index="{{ $qi }}">
                    @foreach($opts as $oi => $ol)
                        <div class="option-row mb-2 d-flex align-items-center gap-2">
                            <span class="option-letter small fw-bold" style="min-width:1.5rem">{{ ($letters[$oi] ?? ($oi + 1)) }}.</span>
                            <input type="text" name="questions[{{ $qi }}][options][]" class="form-control form-control-sm" value="{{ $ol }}" placeholder="Option text">
                            @if($oi >= 2)
                                <button type="button" class="btn btn-sm btn-outline-danger remove-option" aria-label="Remove option">&times;</button>
                            @endif
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary add-option">+ Add option</button>
            </div>
            <div class="mt-2">
                <label class="form-label small">Answer key</label>
                <select name="questions[{{ $qi }}][answer_key]" class="form-select form-select-sm answer-key-select" style="width:auto">
                    @foreach($opts as $oi => $ol)
                        <option value="{{ $oi }}" {{ $answerKey === $oi ? 'selected' : '' }}>{{ $letters[$oi] ?? ($oi + 1) }}</option>
                    @endforeach
                </select>
                <small class="text-muted ms-2">Correct option</small>
            </div>
        </div>
    </div>
</div>
