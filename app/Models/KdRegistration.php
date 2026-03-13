<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KdRegistration extends Model
{
    protected $fillable = [
        'kd_no',
        'full_name',
        'gender',
        'state',
        'full_address',
        'phone_number',
        'registration_date',
        'applicant_signature',
        'cashier_signature',
        'sponsor_kd_no',
        'sponsor_name',
        'placement_kd_no',
        'placement_name',
        'sponsor_signature',
        'user_id',
        'registered_by_user_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by_user_id');
    }

    public function credits(): HasMany
    {
        return $this->hasMany(KdRegistrationCredit::class);
    }
}
