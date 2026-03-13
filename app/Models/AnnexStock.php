<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnexStock extends Model
{
    protected $table = 'annex_stock';

    protected $fillable = [
        'annex_user_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function annexUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'annex_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function incrementStock(int $annexUserId, int $productId, int $qty): void
    {
        $row = self::where('annex_user_id', $annexUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $row->increment('quantity', $qty);
        } else {
            self::create([
                'annex_user_id' => $annexUserId,
                'product_id' => $productId,
                'quantity' => $qty,
            ]);
        }
    }

    public static function getQuantity(int $annexUserId, int $productId): int
    {
        $row = self::where('annex_user_id', $annexUserId)
            ->where('product_id', $productId)
            ->first();

        return $row ? (int) $row->quantity : 0;
    }

    public static function decrementStock(int $annexUserId, int $productId, int $qty): bool
    {
        $row = self::where('annex_user_id', $annexUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $row || (int) $row->quantity < $qty) {
            return false;
        }

        $row->decrement('quantity', $qty);

        return true;
    }
}
