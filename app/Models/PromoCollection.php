<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromoCollection extends Model
{
    protected $table = 'promo_collections';

    protected $fillable = ['promo_name', 'shop_no', 'customer_no', 'customer_name', 'promo_item', 'quantity', 'promo_meta', 'user_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
