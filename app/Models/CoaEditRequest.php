<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One unlock of a COA on an order line, kept forever so every unlock stays
 * on record. Four kinds (type):
 *
 *   request        Quality Control asks to edit a submitted COA; the COA
 *                  approver (QC HOD) or a superadmin approves or rejects.
 *   return         The HOD sends a submitted COA back to the QC staff who
 *                  submitted it, with a reason. They are emailed.
 *   reopen         The HOD unlocks a submitted COA to correct it herself.
 *   return_upload  The HOD sends an uploaded COA file back to the QC staff
 *                  who uploaded it, with a reason. The file is removed and
 *                  they are emailed.
 *
 * return, reopen and return_upload are decided the moment they are made
 * (status approved, requested_by = decided_by = the HOD).
 *
 * previous_signatory_name / previous_submitted_at keep who had submitted
 * (or uploaded) the COA before the unlock, since those columns are cleared
 * on the order line.
 */
class CoaEditRequest extends Model
{
    public const STATUS_PENDING  = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const TYPE_REQUEST       = 'request';
    public const TYPE_RETURN        = 'return';
    public const TYPE_REOPEN        = 'reopen';
    public const TYPE_RETURN_UPLOAD = 'return_upload';

    /** Kinds that unlock a template COA (as opposed to an uploaded file). */
    public const TEMPLATE_TYPES = [
        self::TYPE_REQUEST,
        self::TYPE_RETURN,
        self::TYPE_REOPEN,
    ];

    protected $fillable = [
        'order_id',
        'product_id',
        'order_product_id',
        'type',
        'requested_by',
        'reason',
        'status',
        'decided_by',
        'decided_at',
        'previous_signatory_name',
        'previous_submitted_at',
    ];

    protected $casts = [
        'decided_at'            => 'datetime',
        'previous_submitted_at' => 'datetime',
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

    /**
     * Requests for one order line (order_product.id), newest first. Key by
     * line, not product: the same product can be on an order twice.
     */
    public function scopeForLine($query, int $orderProductId)
    {
        return $query->where('order_product_id', $orderProductId)
            ->latest('id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** Rows written before the type column existed count as requests. */
    public function kind(): string
    {
        return $this->type ?: self::TYPE_REQUEST;
    }

    /** An approved unlock of a template COA (not an uploaded file). */
    public function isTemplateUnlock(): bool
    {
        return $this->isApproved() && in_array($this->kind(), self::TEMPLATE_TYPES, true);
    }

    public function isReturn(): bool
    {
        return in_array($this->kind(), [self::TYPE_RETURN, self::TYPE_RETURN_UPLOAD], true);
    }
}
