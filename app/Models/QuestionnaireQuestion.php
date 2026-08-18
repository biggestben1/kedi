<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuestionnaireQuestion extends Model
{
    public const TYPE_TEXT = 'text';

    public const TYPE_SINGLE_CHOICE = 'single_choice';

    protected $fillable = [
        'questionnaire_id',
        'body',
        'type',
        'sort_order',
    ];

    public function questionnaire(): BelongsTo
    {
        return $this->belongsTo(Questionnaire::class, 'questionnaire_id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionnaireQuestionOption::class, 'questionnaire_question_id')->orderBy('sort_order');
    }
}
