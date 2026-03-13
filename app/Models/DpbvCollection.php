<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DpbvCollection extends Model
{
    protected $table = 'dpbv_collections';

    protected $fillable = ['no', 'code', 'name', 'record_date', 'sc', 'dpbv', 'user_id'];

    protected function casts(): array
    {
        return [
            'record_date' => 'date',
            'dpbv' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
