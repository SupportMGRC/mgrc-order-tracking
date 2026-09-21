<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PickupItem extends Model
{
    protected $fillable = [
        'pickup_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    public function pickup()
    {
        return $this->belongsTo(Pickup::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
