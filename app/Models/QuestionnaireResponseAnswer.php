<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuestionnaireResponseAnswer extends Model
{
    protected $fillable = [
        'questionnaire_response_id',
        'questionnaire_question_id',
        'answer_text',
        'questionnaire_question_option_id',
    ];

    public function response(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireResponse::class, 'questionnaire_response_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'questionnaire_question_id');
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireQuestionOption::class, 'questionnaire_question_option_id');
    }
}
