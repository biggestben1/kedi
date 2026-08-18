<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use SoftDeletes, HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'service_center_code',
        'kid',
        'password',
        'role_id',
        'created_by_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'wallet_balance' => 'decimal:2',
            'kedi_credit_balance' => 'decimal:2',
        ];
    }

    public function canPayWithWallet(float $amount): bool
    {
        return $this->wallet_balance >= $amount;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function createdUsers(): HasMany
    {
        return $this->hasMany(User::class, 'created_by_user_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function dpbvCollections(): HasMany
    {
        return $this->hasMany(DpbvCollection::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->role && $this->role->name === $roleName;
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    /**
     * True for any back-office role allowed into `/admin/*`.
     * Keep this list aligned with the `role:` middleware in `routes/web.php`.
     */
    public function isAdmin(): bool
    {
        $this->loadMissing('role');
        if (!$this->role) {
            return false;
        }

        return in_array($this->role->name, [
            Role::SUPER_ADMIN,
            Role::WHOLESALE_STAFF,
            Role::RESELLER,
            Role::ACCOUNTANT,
            Role::DISPATCH,
            Role::HEADQUARTERS,
            Role::BRANCH,
            Role::SERVICE_CENTER,
            Role::ANNEX,
            Role::CASHIER,
            Role::DISTRIBUTOR,
        ], true);
    }

    public function isWholesaleStaff(): bool
    {
        return $this->hasRole(Role::WHOLESALE_STAFF);
    }

    public function isReseller(): bool
    {
        return $this->hasRole(Role::RESELLER);
    }

    public function isCustomer(): bool
    {
        return $this->hasRole(Role::CUSTOMER);
    }

    public function isAccountant(): bool
    {
        return $this->hasRole(Role::ACCOUNTANT);
    }

    public function isDispatch(): bool
    {
        return $this->hasRole(Role::DISPATCH);
    }

    public function isHeadquarters(): bool
    {
        return $this->hasRole(Role::HEADQUARTERS);
    }

    public function isBranch(): bool
    {
        return $this->hasRole(Role::BRANCH);
    }

    public function isServiceCenter(): bool
    {
        return $this->hasRole(Role::SERVICE_CENTER);
    }

    public function isAnnex(): bool
    {
        return $this->hasRole(Role::ANNEX);
    }

    public function isCashier(): bool
    {
        return $this->hasRole(Role::CASHIER);
    }

    public function isDistributor(): bool
    {
        return $this->hasRole(Role::DISTRIBUTOR);
    }

    /** Cashier / Distributor: sell on behalf of parent HQ/Branch/SC/Annex (same behaviour for now). */
    public function isCashierOrDistributor(): bool
    {
        return $this->isCashier() || $this->isDistributor();
    }

    /**
     * Wallet used for checkout, draft placement, and balance display.
     * Cashier → parent HQ/Branch/SC/Annex wallet. Distributor → own wallet.
     */
    public function walletOwnerForShopping(): User
    {
        $this->loadMissing(['role', 'createdBy.role']);
        if ($this->isCashier() && $this->createdBy && $this->createdBy->role) {
            $ownerRole = $this->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                return $this->createdBy;
            }
        }

        return $this;
    }

    /**
     * User used to resolve HQ bank accounts for wallet top-up (proof transfer).
     * Cashier and Distributor both follow the parent chain when attached to HQ/Branch/SC/Annex.
     */
    public function bankContextUser(): User
    {
        $this->loadMissing(['role', 'createdBy.role']);
        if ($this->isCashierOrDistributor() && $this->createdBy && $this->createdBy->role) {
            $ownerRole = $this->createdBy->role->name;
            if (in_array($ownerRole, ['headquarters', 'branch', 'service_center', 'annex'], true)) {
                return $this->createdBy;
            }
        }

        return $this;
    }
}
