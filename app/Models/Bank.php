<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bank extends Model
{
    public function headquartersUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'headquarters_user_id');
    }
    protected $fillable = [
        'name',
        'code',
        'account_name',
        'account_number',
        'notes',
        'sort_order',
        'is_active',
        'headquarters_user_id',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
