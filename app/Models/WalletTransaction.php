<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'balance_after',
        'reference',
        'status',
        'proof_path',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'approved_at' => 'datetime',
        ];
    }

    public const TYPE_CREDIT = 'credit';
    public const TYPE_DEBIT = 'debit';
    public const STATUS_PENDING = 'pending';
    public const STATUS_ACCEPTED = 'accepted'; // accountant accepted
    public const STATUS_REJECTED = 'rejected';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isCredit(): bool
    {
        return $this->type === self::TYPE_CREDIT;
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
