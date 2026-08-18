<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class QuestionnaireController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $questionnaires = Questionnaire::query()
            ->withCount('responses')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($qq) use ($q) {
                    $qq->where('title', 'like', "%{$q}%")
                        ->orWhere('slug', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.questionnaires.index', [
            'questionnaires' => $questionnaires,
            'q' => $q,
        ]);
    }

    public function create()
    {
        return view('admin.questionnaires.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.body' => ['required', 'string', 'max:5000'],
            'questions.*.type' => ['required', Rule::in([QuestionnaireQuestion::TYPE_TEXT, QuestionnaireQuestion::TYPE_SINGLE_CHOICE])],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:500'],
            'questions.*.answer_key' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->assertQuestionsValid($validated['questions']);

        $slug = $this->uniqueSlug(Str::slug($validated['title']));

        DB::transaction(function () use ($validated, $slug, $request) {
            $questionnaire = Questionnaire::create([
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
                'created_by_user_id' => $request->user()->id,
            ]);
            $this->createQuestionsFromInput($questionnaire, $validated['questions']);
        });

        return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire created.');
    }

    public function edit(Questionnaire $questionnaire)
    {
        $questionnaire->load(['questions.options']);

        return view('admin.questionnaires.edit', [
            'questionnaire' => $questionnaire,
        ]);
    }

    public function update(Request $request, Questionnaire $questionnaire)
    {
        $hasResponses = $questionnaire->responses()->exists();

        if ($hasResponses) {
            $validated = $request->validate([
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
            ]);
            $slug = $this->uniqueSlug(Str::slug($validated['title']), $questionnaire->id);
            $questionnaire->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);

            return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire updated (questions are locked because responses exist).');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'questions' => ['required', 'array', 'min:1'],
            'questions.*.body' => ['required', 'string', 'max:5000'],
            'questions.*.type' => ['required', Rule::in([QuestionnaireQuestion::TYPE_TEXT, QuestionnaireQuestion::TYPE_SINGLE_CHOICE])],
            'questions.*.options' => ['nullable', 'array'],
            'questions.*.options.*' => ['nullable', 'string', 'max:500'],
            'questions.*.answer_key' => ['nullable', 'integer', 'min:0'],
        ]);

        $this->assertQuestionsValid($validated['questions']);

        $slug = $this->uniqueSlug(Str::slug($validated['title']), $questionnaire->id);

        DB::transaction(function () use ($questionnaire, $validated, $slug, $request) {
            $questionnaire->questions()->each(function (QuestionnaireQuestion $q) {
                $q->options()->delete();
            });
            $questionnaire->questions()->delete();
            $questionnaire->update([
                'title' => $validated['title'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_active' => $request->boolean('is_active'),
            ]);
            $this->createQuestionsFromInput($questionnaire, $validated['questions']);
        });

        return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire updated.');
    }

    public function destroy(Questionnaire $questionnaire)
    {
        $questionnaire->delete();

        return redirect()->route('admin.questionnaires.index')->with('success', 'Questionnaire deleted.');
    }

    public function responses(Questionnaire $questionnaire)
    {
        $questionnaire->loadCount('responses');
        $responses = $questionnaire->responses()
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        return view('admin.questionnaires.responses', [
            'questionnaire' => $questionnaire,
            'responses' => $responses,
        ]);
    }

    public function responseShow(Questionnaire $questionnaire, QuestionnaireResponse $response)
    {
        abort_unless((int) $response->questionnaire_id === (int) $questionnaire->id, 404);
        $response->load(['answers.question', 'answers.option', 'user']);

        return view('admin.questionnaires.response_show', [
            'questionnaire' => $questionnaire,
            'response' => $response,
        ]);
    }

    private function uniqueSlug(string $base, ?int $ignoreId = null): string
    {
        $slug = $base !== '' ? $base : 'questionnaire';
        $original = $slug;
        $i = 1;
        while (Questionnaire::query()
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $original.'-'.$i++;
        }

        return $slug;
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsInput
     */
    private function createQuestionsFromInput(Questionnaire $questionnaire, array $questionsInput): void
    {
        foreach ($questionsInput as $i => $row) {
            $type = $row['type'];
            $question = $questionnaire->questions()->create([
                'body' => $row['body'],
                'type' => $type,
                'sort_order' => $i,
            ]);
            if ($type === QuestionnaireQuestion::TYPE_SINGLE_CHOICE) {
                $optionsRaw = $row['options'] ?? [];
                $labels = array_values(array_filter($optionsRaw, fn ($l) => trim((string) $l) !== ''));
                $answerIndex = isset($row['answer_key']) ? (int) $row['answer_key'] : 0;
                if ($answerIndex < 0 || $answerIndex >= count($labels)) {
                    $answerIndex = 0;
                }
                foreach ($labels as $j => $label) {
                    $question->options()->create([
                        'label' => $label,
                        'sort_order' => $j,
                        'is_correct' => $j === $answerIndex,
                    ]);
                }
            }
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $questionsInput
     */
    private function assertQuestionsValid(array $questionsInput): void
    {
        foreach ($questionsInput as $i => $row) {
            $type = $row['type'] ?? '';
            if ($type === QuestionnaireQuestion::TYPE_SINGLE_CHOICE) {
                $labels = array_values(array_filter($row['options'] ?? [], fn ($l) => trim((string) $l) !== ''));
                if (count($labels) < 2) {
                    throw ValidationException::withMessages([
                        "questions.{$i}.options" => 'Multiple-choice questions need at least two non-empty options.',
                    ]);
                }
                $answerIndex = isset($row['answer_key']) ? (int) $row['answer_key'] : 0;
                if ($answerIndex < 0 || $answerIndex >= count($labels)) {
                    throw ValidationException::withMessages([
                        "questions.{$i}.answer_key" => 'Choose a valid correct answer (A, B, …) for this question.',
                    ]);
                }
            }
        }
    }
}
