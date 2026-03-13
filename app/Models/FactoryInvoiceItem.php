<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FactoryInvoiceItem extends Model
{
    protected $table = 'factory_invoice_items';

    protected $fillable = [
        'factory_invoice_id',
        'product_id',
        'item_code',
        'product_name',
        'quantity',
        'status',
        'cost_price',
        'line_total',
        'is_brought',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'is_brought' => 'boolean',
        ];
    }

    public function factoryInvoice(): BelongsTo
    {
        return $this->belongsTo(FactoryInvoice::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return FactoryInvoice::statusOptions()[$this->status] ?? $this->status;
    }
}
