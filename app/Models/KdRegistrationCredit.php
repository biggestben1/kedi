<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KdRegistrationCredit extends Model
{
    protected $fillable = [
        'kd_registration_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';

    public function kdRegistration(): BelongsTo
    {
        return $this->belongsTo(KdRegistration::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isDebit(): bool
    {
        return $this->type === self::TYPE_DEBIT;
    }
}
