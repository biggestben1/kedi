<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactoryInvoice extends Model
{
    public const STATUS_ACHIEVEMENT = 'achievement';
    public const STATUS_BORROW = 'borrow';
    public const STATUS_DPBV = 'dpbv';
    public const STATUS_PROMO = 'promo';
    public const STATUS_BACKORDER = 'backorder';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_ACHIEVEMENT => 'Achievement',
            self::STATUS_BORROW => 'Borrow',
            self::STATUS_DPBV => 'DPBV',
            self::STATUS_PROMO => 'Promo',
            self::STATUS_BACKORDER => 'Backorder',
        ];
    }

    protected $fillable = ['invoice_number', 'factory_name', 'invoice_date', 'notes', 'stock_added_at'];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'stock_added_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(FactoryInvoiceItem::class)->orderBy('id');
    }
}
