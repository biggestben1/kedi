<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CollectionCenterMove extends Model
{
    protected $fillable = [
        'order_id',
        'order_group_id',
        'from_branch_user_id',
        'to_branch_user_id',
        'moved_by_user_id',
        'reason',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderGroup(): BelongsTo
    {
        return $this->belongsTo(OrderGroup::class);
    }

    public function fromBranch(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_branch_user_id');
    }

    public function toBranch(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_branch_user_id');
    }

    public function movedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by_user_id');
    }
}
