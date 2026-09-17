<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderGroup extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'collection_branch_id',
        'name',
        'kd_id',
        'customer_name',
        'status',
        'payment_method',
        'payment_breakdown',
        'total_amount',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'payment_breakdown' => 'array',
            'total_amount' => 'decimal:2',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function collectionBranch(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collection_branch_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function collectionCenterMoves(): HasMany
    {
        return $this->hasMany(CollectionCenterMove::class);
    }

    public function draftOrders(): HasMany
    {
        return $this->orders()->where('status', Order::STATUS_DRAFT);
    }

    public function isOpen(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }

    public function draftTotal(): float
    {
        return (float) $this->draftOrders()->sum('subtotal');
    }

    public function displayName(): string
    {
        return $this->name ?: 'Order Group #'.$this->id;
    }
}
