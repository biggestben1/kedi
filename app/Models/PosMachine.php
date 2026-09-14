<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosMachine extends Model
{
    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'branch_user_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}

