<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    protected $fillable = [
        'purchase_date',
        'name',
        'category',
        'serial_number',
        'location',
        'cost',
        'accumulated_depreciation',
        'status',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'purchase_date' => 'date',
            'cost' => 'decimal:2',
            'accumulated_depreciation' => 'decimal:2',
        ];
    }

    public function getNetBookValueAttribute(): float
    {
        return (float) $this->cost - (float) $this->accumulated_depreciation;
    }
}

