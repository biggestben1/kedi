<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceCenterStock extends Model
{
    protected $table = 'service_center_stock';

    protected $fillable = [
        'service_center_user_id',
        'product_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function serviceCenterUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'service_center_user_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public static function incrementStock(int $serviceCenterUserId, int $productId, int $qty): void
    {
        $row = self::where('service_center_user_id', $serviceCenterUserId)
            ->where('product_id', $productId)
            ->lockForUpdate()
            ->first();

        if ($row) {
            $row->increment('quantity', $qty);
        } else {
            self::create([
                'service_center_user_id' => $serviceCenterUserId,
                'product_id' => $productId,
                'quantity' => $qty,
            ]);
        }
    }

    public static function getQuantity(int $serviceCenterUserId, int $productId): int
    {
        $row = self::where('service_center_user_id', $serviceCenterUserId)
            ->where('product_id', $productId)
            ->first();

        return $row ? (int) $row->quantity : 0;
    }

    public static function decrementStock(int $serviceCenterUserId, int $productId, int $qty): bool
    {
        $row = self::where('service_center_user_id', $serviceCenterUserId)
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
