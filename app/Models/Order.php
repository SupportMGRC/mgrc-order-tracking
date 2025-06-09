<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'user_id',
        'order_placed_by',
        'delivered_by',
        'collected_by',
        'signature_data',
        'signature_date',
        'signature_ip',
        'order_date',
        'order_time',
        'status',
        'delivery_type',
        'pickup_delivery_date',
        'pickup_delivery_time',
        'remarks',
        'item_ready_at',
        'delivery_address',
        'order_photo',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'order_date' => 'date',
        'order_time' => 'datetime',
        'pickup_delivery_date' => 'date',
        'pickup_delivery_time' => 'datetime',
        'item_ready_at' => 'datetime',
        'signature_date' => 'datetime',
    ];

    /**
     * Get the customer that owns the order.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the user that created the order.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The products that belong to the order.
     */
    public function products()
    {
        return $this->belongsToMany(Product::class, 'order_product')
            ->withPivot('id', 'quantity', 'batch_number', 'patient_name', 'remarks', 'qc_document_number', 'prepared_by', 'status')
            ->withTimestamps();
    }
} 