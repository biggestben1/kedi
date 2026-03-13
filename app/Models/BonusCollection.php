<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BonusCollection extends Model
{
    protected $table = 'bonus_collections';

    protected $fillable = ['no', 'code', 'name', 'record_date', 'sc', 'grade', 'honorary', 'total', 'user_id', 'is_disbursed', 'disbursed_at', 'disbursed_by_user_id'];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'total' => 'decimal:2',
            'is_disbursed' => 'boolean',
            'disbursed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function disbursedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disbursed_by_user_id');
    }
}
