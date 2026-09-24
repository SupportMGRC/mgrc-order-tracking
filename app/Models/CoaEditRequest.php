<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A request to unlock a submitted COA so it can be filled in again.
 *
 * Raised by Quality Control, decided by a COA approver (QC HOD) or a
 * superadmin. Rows are never deleted, so every unlock stays on record.
 */
class CoaEditRequest extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_id',
        'product_id',
        'requested_by',
        'reason',
        'status',
        'decided_by',
        'decided_at',
    ];

    protected $casts = [
        'decided_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider()
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /** Requests for one order line, newest first. */
    public function scopeForLine($query, int $orderId, int $productId)
    {
        return $query->where('order_id', $orderId)
            ->where('product_id', $productId)
            ->latest('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
