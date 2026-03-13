<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KediKitBackOrder extends Model
{
    protected $fillable = [
        'kedi_kit_id',
        'purchase_id',
        'buyer_user_id',
        'quantity_pending',
        'quantity_fulfilled',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_pending' => 'integer',
            'quantity_fulfilled' => 'integer',
        ];
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_FULFILLED = 'fulfilled';
    public const STATUS_CANCELLED = 'cancelled';

    public function kit(): BelongsTo
    {
        return $this->belongsTo(KediKit::class, 'kedi_kit_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(KediKitPurchase::class, 'purchase_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_user_id');
    }
}
