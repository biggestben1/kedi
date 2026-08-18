@extends('layouts.admin')

@section('title', 'New questionnaire')

@section('content')
    <div class="page-header">
        <h1 class="page-title">New questionnaire</h1>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('admin.questionnaires.index') }}">Questionnaires</a></li>
            <li class="breadcrumb-item active">Create</li>
        </ol>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.questionnaires.store') }}" id="questionnaireForm">
        @csrf
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Details</h3></div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ old('title') }}" required maxlength="255">
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea>
                </div>
                <input type="hidden" name="is_active" value="0">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                    <label class="form-check-label" for="is_active">Published (users can see it)</label>
                </div>
            </div>
        </div>

        <hr class="my-4">
        <h6 class="mb-3">Questions</h6>
        <p class="text-muted small mb-3">
            Add one or more questions. For multiple-choice questions, enter options and set the correct answer in <strong>Answer key</strong>.
        </p>

        <div id="questions-container">
            @php
                $oldQs = old('questions', [['body' => '', 'type' => 'single_choice', 'options' => ['', ''], 'answer_key' => 0]]);
            @endphp
            @foreach($oldQs as $qi => $qrow)
                @include('admin.questionnaires.partials.question-block', [
                    'qi' => $qi,
                    'qrow' => $qrow,
                    'showRemove' => count($oldQs) > 1,
                ])
            @endforeach
        </div>

        <button type="button" id="add-question" class="btn btn-outline-secondary btn-sm mb-3">+ Add another question</button>

        <div class="mt-3">
            <button type="submit" class="btn btn-primary">Save questionnaire</button>
            <a href="{{ route('admin.questionnaires.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        var container = document.getElementById('questions-container');
        var addBtn = document.getElementById('add-question');
        var template = container.querySelector('.question-block').outerHTML;
        var index = container.querySelectorAll('.question-block').length;

        function letterForIndex(n) {
            return String.fromCharCode(65 + Math.min(n, 25));
        }

        function toggleMcq(block) {
            var select = block.querySelector('.q-type');
            var mcq = block.querySelector('.mcq-wrap');
            if (!select || !mcq) return;
            mcq.style.display = select.value === 'single_choice' ? '' : 'none';
        }

        function refreshAnswerKeySelect(block) {
            var list = block.querySelector('.question-options-list');
            var select = block.querySelector('.answer-key-select');
            if (!list || !select) return;
            var rows = list.querySelectorAll('.option-row');
            var count = rows.length;
            var prevVal = select.value;
            select.innerHTML = '';
            for (var k = 0; k < count; k++) {
                var opt = document.createElement('option');
                opt.value = k;
                opt.textContent = letterForIndex(k);
                select.appendChild(opt);
            }
            select.value = (prevVal !== '' && parseInt(prevVal, 10) < count) ? prevVal : '0';
        }

        function renumber() {
            container.querySelectorAll('.question-block').forEach(function (block, i) {
                block.querySelector('.question-num').textContent = i + 1;
                block.dataset.index = i;
                var list = block.querySelector('.question-options-list');
                if (list) list.dataset.questionIndex = i;
                block.querySelectorAll('[name]').forEach(function (input) {
                    input.name = input.name.replace(/questions\[\d+\]/, 'questions[' + i + ']');
                });
                block.querySelector('.remove-question').classList.toggle('d-none', container.querySelectorAll('.question-block').length === 1);
                refreshAnswerKeySelect(block);
                toggleMcq(block);
            });
        }

        container.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('q-type')) {
                toggleMcq(e.target.closest('.question-block'));
            }
        });

        container.addEventListener('click', function (e) {
            if (e.target.classList.contains('remove-question')) {
                e.target.closest('.question-block').remove();
                renumber();
                return;
            }
            if (e.target.classList.contains('add-option')) {
                var block = e.target.closest('.question-block');
                var idx = block.dataset.index;
                var list = block.querySelector('.question-options-list');
                var count = list.querySelectorAll('.option-row').length;
                if (count >= 26) return;
                var letter = letterForIndex(count);
                var row = document.createElement('div');
                row.className = 'option-row mb-2 d-flex align-items-center gap-2';
                row.innerHTML = '<span class="option-letter small fw-bold" style="min-width:1.5rem">' + letter + '.</span>' +
                    '<input type="text" name="questions[' + idx + '][options][]" class="form-control form-control-sm" placeholder="Option text">' +
                    '<button type="button" class="btn btn-sm btn-outline-danger remove-option" aria-label="Remove option">&times;</button>';
                list.appendChild(row);
                refreshAnswerKeySelect(block);
                return;
            }
            if (e.target.classList.contains('remove-option')) {
                var row = e.target.closest('.option-row');
                var block = row.closest('.question-block');
                var list = block.querySelector('.question-options-list');
                if (list.querySelectorAll('.option-row').length <= 2) return;
                row.remove();
                var rows = list.querySelectorAll('.option-row');
                rows.forEach(function (r, i) {
                    r.querySelector('.option-letter').textContent = letterForIndex(i) + '.';
                    r.querySelector('input').name = 'questions[' + block.dataset.index + '][options][]';
                });
                refreshAnswerKeySelect(block);
            }
        });

        addBtn.addEventListener('click', function () {
            var div = document.createElement('div');
            div.innerHTML = template.replace(/questions\[\d+\]/g, 'questions[' + index + ']');
            var block = div.firstElementChild;
            block.querySelector('.remove-question').classList.remove('d-none');
            block.querySelector('.question-text').value = '';
            block.querySelectorAll('.question-options-list input').forEach(function (inp) { inp.value = ''; });
            container.appendChild(block);
            index++;
            renumber();
        });

        renumber();
    });
    </script>
    @endpush
@endsection
