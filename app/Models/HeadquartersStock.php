<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class HeadquartersStock extends Model
{
    protected $table = 'headquarters_stock';

    protected $fillable = [
        'headquarters_user_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function headquartersUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headquarters_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Get available quantity for a product at headquarters (0 if no record). */
    public static function getQuantity(int $headquartersUserId, int $productId): int
    {
        $hs = self::where('headquarters_user_id', $headquartersUserId)
            ->where('product_id', $productId)
            ->first();

        return $hs ? (int) $hs->quantity : 0;
    }

    /** Increment headquarters stock. Creates record if it doesn't exist. */
    public static function incrementStock(int $headquartersUserId, int $productId, int $qty): void
    {
        $hs = self::where('headquarters_user_id', $headquartersUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($hs) {
            $hs->increment('quantity', $qty);
        } else {
            self::create([
                'headquarters_user_id' => $headquartersUserId,
                'product_id' => $productId,
                'quantity' => $qty,
            ]);
        }
    }

    /** Decrement headquarters stock. Returns true if successful. */
    public static function decrementStock(int $headquartersUserId, int $productId, int $qty): bool
    {
        $hs = self::where('headquarters_user_id', $headquartersUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $hs || (int) $hs->quantity < $qty) {
            return false;
        }

        $hs->decrement('quantity', $qty);

        return true;
    }
}
