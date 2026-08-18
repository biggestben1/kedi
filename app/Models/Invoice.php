<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_number',
        'user_id',
        'branch_user_id',
        'customer_name',
        'sc_referral_code',
        'coupon_code',
        'customer_email',
        'customer_phone',
        'customer_address',
        'invoice_date',
        'due_date',
        'subtotal',
        'tax',
        'discount',
        'coupon_discount_amount',
        'total',
        'status',
        'payment_method',
        'pos_amount_paid',
        'payment_breakdown',
        'notes',
        'is_approved',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'decimal:2',
            'tax' => 'decimal:2',
            'discount' => 'decimal:2',
            'coupon_discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'pos_amount_paid' => 'decimal:2',
            'payment_breakdown' => 'array',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function branchUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'branch_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    public function backOrders(): HasMany
    {
        return $this->hasMany(BackOrder::class);
    }
}
