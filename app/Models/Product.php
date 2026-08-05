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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'requires_patient_details' => 'boolean',
    ];

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
                'coa_updated_at'
            )
            ->withTimestamps();
    }
}
