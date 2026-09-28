<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Invoice created without a stock check and sent to a collection center (branch).
 * Stock is deducted from the branch when the linked order is collected.
 */
class CollectionInvoice extends Model
{
    /** Item code prefix for order lines that do not match a product (no stock to deduct at collection). */
    public const NON_STOCK_ITEM_PREFIX = 'CINV-';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'order_id',
        'customer_name',
        'customer_phone',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
        ];
    }

    public static function numberFor(int $id): string
    {
        return 'CINV-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(CollectionInvoiceItem::class);
    }

    public function isCollected(): bool
    {
        return $this->order?->collected_at !== null;
    }
}
