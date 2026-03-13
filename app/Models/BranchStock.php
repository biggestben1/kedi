<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchStock extends Model
{
    protected $table = 'branch_stock';

    protected $fillable = [
        'branch_user_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function branchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Increment branch stock. Creates record if it doesn't exist. */
    public static function incrementStock(int $branchUserId, int $productId, int $qty): void
    {
        $bs = self::where('branch_user_id', $branchUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($bs) {
            $bs->increment('quantity', $qty);
        } else {
            self::create([
                'branch_user_id' => $branchUserId,
                'product_id' => $productId,
                'quantity' => $qty,
            ]);
        }
    }

    /** Get available quantity for a product at a branch (0 if no record). */
    public static function getQuantity(int $branchUserId, int $productId): int
    {
        $bs = self::where('branch_user_id', $branchUserId)
            ->where('product_id', $productId)
            ->first();

        return $bs ? (int) $bs->quantity : 0;
    }

    /** Decrement branch stock. Returns true if successful. */
    public static function decrementStock(int $branchUserId, int $productId, int $qty): bool
    {
        $bs = self::where('branch_user_id', $branchUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if (! $bs || (int) $bs->quantity < $qty) {
            return false;
        }

        $bs->decrement('quantity', $qty);

        return true;
    }
}
