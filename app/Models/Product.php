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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    /**
     * The orders that belong to the product.
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_product')
            ->withPivot('id', 'quantity', 'price', 'batch_number', 'patient_name', 'remarks', 'qc_document_number', 'prepared_by', 'status', 'coa_required', 'coa_template', 'coa_number', 'coa_product_date', 'coa_mfg_date', 'coa_expiry_date', 'coa_viable_cell_count', 'coa_signature_date', 'coa_morphology_image', 'coa_updated_by', 'coa_updated_at')
            ->withTimestamps();
    }
} 