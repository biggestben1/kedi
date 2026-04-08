<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'invoice_number',
        'user_id',
        'branch_user_id',
        'invoice_id',
        'subtotal',
        'shipping_cost',
        'total_bv',
        'total_pv',
        'payment_method',
        'status',
        'delivered_at',
        'packed_at',
        'shipped_at',
        'tracking_number',
        'delivery_courier',
        'shipping_address',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_phone',
        'kd_id',
        'customer_name',
        'delivery_type',
        'is_dpbv_order',
        'coupon_id',
        'coupon_code',
        'discount_amount',
        'sc_referral_code',
        'notes',
    ];

    public const DELIVERY_WALK_IN = 'walk_in';
    public const DELIVERY_SHIP = 'ship';

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_cost' => 'decimal:2',
            'total_bv' => 'decimal:2',
            'total_pv' => 'decimal:2',
            'delivered_at' => 'datetime',
            'packed_at' => 'datetime',
            'shipped_at' => 'datetime',
        ];
    }

    public function isDelivered(): bool
    {
        return $this->delivered_at !== null;
    }

    public const PAYMENT_WALLET = 'wallet';
    public const PAYMENT_PAY_ON_DELIVERY = 'pay_on_delivery';
    public const PAYMENT_DPBV = 'dpbv';
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_PACKED = 'packed';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_DRAFT = 'draft';

    /** Statuses that dispatch can work with (paid and beyond). */
    public static function dispatchableStatuses(): array
    {
        return [self::STATUS_PAID, self::STATUS_PACKED, self::STATUS_SHIPPED, self::STATUS_DELIVERED, self::STATUS_COMPLETED];
    }

    /** Orders placed by users with role wholesale_staff or reseller. */
    public function scopeWholesale(Builder $query): Builder
    {
        return $query->whereHas('user', function ($q) {
            $q->whereHas('role', function ($r) {
                $r->whereIn('name', ['wholesale_staff', 'reseller']);
            });
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Generate next order number (e.g. ORD-000001). */
    public static function generateOrderNumber(): string
    {
        $last = Order::orderByDesc('id')->first();
        $next = $last ? ((int) preg_replace('/[^0-9]/', '', $last->invoice_number ?? (string) $last->id)) + 1 : 1;

        return 'ORD-' . str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    /** Display tracking number (external tracking_number, invoice_number, or ORD-{id}) */
    public function getTrackingNumberAttribute($value): string
    {
        if (! empty($value)) {
            return $value;
        }
        return $this->attributes['invoice_number'] ?? 'ORD-' . $this->id;
    }
}
