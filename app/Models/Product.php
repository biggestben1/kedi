<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use SoftDeletes;
    /** Role names that see member price; everyone else sees retail price (cost_price). */
    public const MEMBER_ROLES = ['super_admin', 'wholesale_staff', 'reseller', 'customer', 'branch', 'headquarters'];

    protected $fillable = [
        'category_id',
        'item_code',
        'name',
        'pack_size',
        'bv',
        'pv',
        'price',
        'cost_price',
        'stock',
        'expiry_date',
        'batch_number',
        'min_stock',
        'image',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'bv' => 'decimal:2',
            'pv' => 'decimal:2',
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'stock' => 'integer',
            'expiry_date' => 'date',
            'min_stock' => 'integer',
            'is_active' => 'boolean',
            'can_use_dpbv' => 'boolean',
        ];
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->pack_size
            ? "{$this->name} ({$this->pack_size})"
            : $this->name;
    }

    public function getFormattedPriceAttribute(): string
    {
        return '₦' . number_format($this->price, 0);
    }

    public function getFormattedCostPriceAttribute(): string
    {
        return $this->cost_price !== null ? '₦' . number_format($this->cost_price, 0) : '—';
    }

    /** Retail price for display (cost_price if set, else 20% above member price). */
    public function getFormattedRetailPriceAttribute(): string
    {
        $retail = $this->cost_price !== null && (float) $this->cost_price > 0
            ? (float) $this->cost_price
            : round((float) $this->price * 1.2, 2);
        return '₦' . number_format($retail, 0);
    }

    /** Price to show/charge for the given user (member price vs retail price). */
    public function getPriceForUser(?\App\Models\User $user): float
    {
        $useRetail = $user === null || ! in_array($user->role?->name ?? '', self::MEMBER_ROLES, true);
        if (! $useRetail) {
            return (float) $this->price;
        }
        // Retail: use cost_price (Retail Price from price list) or fallback to 20% above member price
        if ($this->cost_price !== null && (float) $this->cost_price > 0) {
            return (float) $this->cost_price;
        }
        return round((float) $this->price * 1.2, 2);
    }

    public function getFormattedPriceForUser(?\App\Models\User $user): string
    {
        return '₦' . number_format($this->getPriceForUser($user), 0);
    }

    /** Display price for the currently authenticated user (for use in views). */
    public function getDisplayPriceAttribute(): float
    {
        return $this->getPriceForUser(auth()->user());
    }

    public function getFormattedDisplayPriceAttribute(): string
    {
        return $this->getFormattedPriceForUser(auth()->user());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /** Full URL for the product image (served via API to avoid 403 on direct storage). */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        return Storage::disk('public')->exists($this->image)
            ? url('api/v1/storage/' . $this->image)
            : null;
    }
}
