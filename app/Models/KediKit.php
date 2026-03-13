<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KediKit extends Model
{
    protected $fillable = [
        'category',
        'price',
        'quantity',
        'description',
        'created_by_user_id',
        'is_old',
        'purchased_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'is_old' => 'boolean',
        ];
    }

    public const CATEGORY_ENGLISH = 'english';
    public const CATEGORY_FRENCH = 'french';

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(KediKitItem::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(KediKitPurchase::class, 'kedi_kit_id');
    }

    public function backOrders(): HasMany
    {
        return $this->hasMany(KediKitBackOrder::class, 'kedi_kit_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match($this->category) {
            self::CATEGORY_ENGLISH => 'English',
            self::CATEGORY_FRENCH => 'French',
            default => ucfirst($this->category),
        };
    }
}
