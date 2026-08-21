<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'name',
        'phoneNo',
        'email',
        'address',
        'accStatus',
        'cust_group',
        'preferredContactMethod',
        'source',
        'tag',
        'remarks',
        'lastInteractionDate',
        'lastUpdate',
        'userID',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'lastInteractionDate' => 'date',
        'lastUpdate' => 'datetime',
    ];

    /**
     * Get the user associated with the customer.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'userID');
    }

    /**
     * Get the orders for the customer.
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'customer_id');
    }

    /**
     * Get the visits for the customer.
     */
    public function visits()
    {
        return $this->hasMany(Visit::class, 'customer_id');
    }
}