<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KediKitItem extends Model
{
    protected $fillable = [
        'kedi_kit_id',
        'kedi_kit_purchase_id',
        'kd_no',
        'is_old',
        'purchased_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_old' => 'boolean',
        ];
    }

    // Auto-format kd_no to uppercase and trimmed
    public function setKdNoAttribute($value)
    {
        $this->attributes['kd_no'] = strtoupper(trim($value));
    }

    public function kit(): BelongsTo
    {
        return $this->belongsTo(KediKit::class, 'kedi_kit_id');
    }

    public function purchasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'purchased_by_user_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(KediKitPurchase::class, 'kedi_kit_purchase_id');
    }

    public function registration(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(KdRegistration::class, 'kd_no', 'kd_no');
    }
}
