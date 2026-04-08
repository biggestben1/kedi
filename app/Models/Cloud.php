<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Global footer content stored in the `cloud` table (single row or latest by id).
 */
class Cloud extends Model
{
    protected $table = 'cloud';

    protected $fillable = [
        'body',
    ];
}
