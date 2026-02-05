<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'description'];

    public const SUPER_ADMIN = 'super_admin';
    public const WHOLESALE_STAFF = 'wholesale_staff';
    public const RESELLER = 'reseller';
    public const CUSTOMER = 'customer';
    public const ACCOUNTANT = 'accountant';
    public const DISPATCH = 'dispatch';

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->name === self::SUPER_ADMIN;
    }

    public function isWholesaleStaff(): bool
    {
        return $this->name === self::WHOLESALE_STAFF;
    }

    public function isReseller(): bool
    {
        return $this->name === self::RESELLER;
    }

    public function isCustomer(): bool
    {
        return $this->name === self::CUSTOMER;
    }

    public function isAccountant(): bool
    {
        return $this->name === self::ACCOUNTANT;
    }

    public function isDispatch(): bool
    {
        return $this->name === self::DISPATCH;
    }
}
