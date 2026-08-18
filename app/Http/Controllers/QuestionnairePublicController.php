<?php

namespace App\Http\Controllers;

use App\Models\Questionnaire;
use App\Models\QuestionnaireQuestion;
use App\Models\QuestionnaireResponse;
use App\Models\QuestionnaireResponseAnswer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class QuestionnairePublicController extends Controller
{
    public function index()
    {
        $questionnaires = Questionnaire::query()
            ->where('is_active', true)
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'description']);

        return view('questionnaires.index', [
            'questionnaires' => $questionnaires,
        ]);
    }

    public function show(string $slug)
    {
        $questionnaire = Questionnaire::query()->where('slug', $slug)->first();

        if (! $questionnaire) {
            abort(404, 'This questionnaire does not exist.');
        }

        if (! $questionnaire->is_active) {
            return view('questionnaires.unavailable', [
                'questionnaire' => $questionnaire,
            ]);
        }

        $questionnaire->load(['questions.options']);

        ['response' => $response, 'score' => $score] = $this->loadSubmittedResponse($questionnaire);

        return view('questionnaires.show', [
            'questionnaire' => $questionnaire,
            'response' => $response,
            'score' => $score,
        ]);
    }

    public function store(Request $request, string $slug)
    {
        $questionnaire = Questionnaire::query()->where('slug', $slug)->where('is_active', true)->first();

        if (! $questionnaire) {
            return back()->withErrors(['form' => 'This questionnaire is not available.'])->withInput();
        }

        $questionnaire->load('questions.options');

        $rules = [
            'respondent_name' => ['nullable', 'string', 'max:255'],
            'respondent_email' => ['nullable', 'email', 'max:255'],
            'answers' => ['required', 'array'],
        ];

        foreach ($questionnaire->questions as $q) {
            $key = 'answers.'.$q->id;
            if ($q->type === QuestionnaireQuestion::TYPE_TEXT) {
                $rules[$key] = ['required', 'string', 'max:10000'];
            } else {
                $optionIds = $q->options->pluck('id')->all();
                $rules[$key] = ['required', Rule::in($optionIds)];
            }
        }

        $validated = $request->validate($rules);

        foreach ($questionnaire->questions as $q) {
            $val = $validated['answers'][$q->id] ?? null;
            if ($q->type === QuestionnaireQuestion::TYPE_SINGLE_CHOICE) {
                $optId = (int) $val;
                $belongs = $q->options->contains('id', $optId);
                if (! $belongs) {
                    return back()->withErrors(['answers.'.$q->id => 'Invalid option.'])->withInput();
                }
            }
        }

        $response = DB::transaction(function () use ($questionnaire, $validated, $request) {
            $response = QuestionnaireResponse::create([
                'questionnaire_id' => $questionnaire->id,
                'user_id' => $request->user()?->id,
                'respondent_name' => $validated['respondent_name'] ?? null,
                'respondent_email' => $validated['respondent_email'] ?? null,
            ]);

            foreach ($questionnaire->questions as $q) {
                $val = $validated['answers'][$q->id];
                if ($q->type === QuestionnaireQuestion::TYPE_TEXT) {
                    QuestionnaireResponseAnswer::create([
                        'questionnaire_response_id' => $response->id,
                        'questionnaire_question_id' => $q->id,
                        'answer_text' => $val,
                        'questionnaire_question_option_id' => null,
                    ]);
                } else {
                    QuestionnaireResponseAnswer::create([
                        'questionnaire_response_id' => $response->id,
                        'questionnaire_question_id' => $q->id,
                        'answer_text' => null,
                        'questionnaire_question_option_id' => (int) $val,
                    ]);
                }
            }

            return $response;
        });

        return redirect()
            ->route('questionnaires.show', $questionnaire->slug)
            ->with('questionnaire_response_id', $response->id);
    }

    public function thanks(string $slug)
    {
        $questionnaire = Questionnaire::query()->where('slug', $slug)->where('is_active', true)->firstOrFail();

        return redirect()->route('questionnaires.show', $questionnaire->slug);
    }

    /**
     * @return array{response: ?QuestionnaireResponse, score: array{correct: int, total: int}}
     */
    private function loadSubmittedResponse(Questionnaire $questionnaire): array
    {
        $response = null;
        $responseId = session('questionnaire_response_id');
        if ($responseId) {
            $response = QuestionnaireResponse::query()
                ->where('questionnaire_id', $questionnaire->id)
                ->with(['answers.option', 'answers.question'])
                ->find($responseId);
        }

        $score = ['correct' => 0, 'total' => 0];
        if ($response) {
            foreach ($questionnaire->questions as $q) {
                if ($q->type !== QuestionnaireQuestion::TYPE_SINGLE_CHOICE) {
                    continue;
                }
                $score['total']++;
                $userAnswer = $response->answers->firstWhere('questionnaire_question_id', $q->id);
                $correctOption = $q->options->firstWhere('is_correct', true);
                if ($userAnswer && $correctOption && (int) $userAnswer->questionnaire_question_option_id === (int) $correctOption->id) {
                    $score['correct']++;
                }
            }
        }

        return ['response' => $response, 'score' => $score];
    }
}
