<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KediKitPurchase extends Model
{
    protected $fillable = [
        'kedi_kit_id',
        'buyer_user_id',
        'seller_user_id',
        'quantity',
        'unit_price',
        'total_price',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_COMPLETED = 'completed';

    public function kit(): BelongsTo
    {
        return $this->belongsTo(KediKit::class, 'kedi_kit_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function backOrders(): HasMany
    {
        return $this->hasMany(KediKitBackOrder::class, 'purchase_id');
    }

    /**
     * Check if all items in this purchase have been registered.
     */
    public function isFullyRegistered(): bool
    {
        // Now that we decrement quantity upon registration, 
        // a quantity of 0 means the purchase is fully registered.
        return $this->quantity <= 0;
    }
}
