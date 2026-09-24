<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'coa_template',
        'requires_patient_details',
        'usage_type',
        'is_active',
        'receiving_department',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'requires_patient_details' => 'boolean',
    ];

    /**
     * What a product is used for.
     *
     *   'order'  -> sold on New Order (stock, price, COA apply)
     *   'pickup' -> collected by the despatcher on New Pickup (e.g. Blood Tube)
     *
     * Column is usage_type, not usage: USAGE is a reserved word in MySQL.
     */
    public const USAGE_ORDER  = 'order';
    public const USAGE_PICKUP = 'pickup';

    public const USAGE_TYPES = [
        self::USAGE_ORDER  => 'Order',
        self::USAGE_PICKUP => 'Pickup',
    ];

    /**
     * Active products are the ones staff can still pick on New Order and
     * New Pickup. Inactive means discontinued: hidden from those dropdowns,
     * but kept in Product Management so past orders and pickups keep their
     * item details, COA data and batch numbers.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeForOrders($query)
    {
        return $query->where('usage_type', self::USAGE_ORDER);
    }

    public function scopeForPickups($query)
    {
        return $query->where('usage_type', self::USAGE_PICKUP);
    }

    public function isPickupItem(): bool
    {
        return $this->usage_type === self::USAGE_PICKUP;
    }

    /**
     * for test products such as NK Immunophenotyping test: the order
     * line must carry a patient name and IC, and Quantity and Remarks are
     * hidden because a blood test is not ordered by the unit.
     */
    public function requiresPatientDetails(): bool
    {
        return (bool) $this->requires_patient_details;
    }

    /**
     * The orders that belong to the product.
     *
     * The pivot column list must be complete. Anything missing here is
     * silently dropped on save rather than raising an error, so this list
     * is kept identical to the one in Order::products().
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product')
            ->withPivot(
                'id',
                'quantity',
                'batch_number',
                'patient_name',
                'patient_ic',
                'remarks',
                'qc_document_number',
                'prepared_by',
                'status',
                'coa_required',
                'coa_template',
                'coa_number',
                'coa_product_date',
                'coa_mfg_date',
                'coa_expiry_date',
                'coa_viable_cell_count',
                'coa_signature_date',
                'coa_immuno_cd73',
                'coa_immuno_cd90',
                'coa_immuno_cd105',
                'coa_immuno_negative',
                'coa_morphology_image',
                'coa_document',
                'coa_document_uploaded_by',
                'coa_document_uploaded_at',
                'coa_updated_by',
                'coa_updated_at',
                'coa_submitted_by',
                'coa_submitted_at',
                'coa_signatory_name'
            )
            ->withTimestamps();
    }
}
