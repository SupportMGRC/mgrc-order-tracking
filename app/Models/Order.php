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
        'order_photos',
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
        'order_photos' => 'array',
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

    /**
     * Get all order photos (including single photo for backward compatibility)
     *
     * @return array
     */
    public function getAllPhotos()
    {
        $photos = [];
        
        // Include photos from order_photos JSON array
        if ($this->order_photos && is_array($this->order_photos)) {
            $photos = array_merge($photos, $this->order_photos);
        }
        
        // Include single photo for backward compatibility if not already in order_photos
        if ($this->order_photo && !in_array($this->order_photo, $photos)) {
            $photos[] = $this->order_photo;
        }
        
        return array_unique($photos);
    }

    /**
     * Add a photo to the order
     *
     * @param string $filename
     * @return void
     */
    public function addPhoto($filename)
    {
        $photos = $this->order_photos ?? [];
        $photos[] = $filename;
        $this->order_photos = $photos;
        
        // Keep backward compatibility by setting first photo as order_photo
        if (!$this->order_photo) {
            $this->order_photo = $filename;
        }
    }

    /**
     * Remove a photo from the order
     *
     * @param string $filename
     * @return void
     */
    public function removePhoto($filename)
    {
        $photos = $this->order_photos ?? [];
        $photos = array_filter($photos, function($photo) use ($filename) {
            return $photo !== $filename;
        });
        $this->order_photos = array_values($photos);
        
        // Update order_photo if it was the removed photo
        if ($this->order_photo === $filename) {
            $this->order_photo = !empty($this->order_photos) ? $this->order_photos[0] : null;
        }
    }

    /**
     * Check if order has any photos
     *
     * @return bool
     */
    public function hasPhotos()
    {
        return !empty($this->getAllPhotos());
    }

    /**
     * Get the count of photos
     *
     * @return int
     */
    public function getPhotosCount()
    {
        return count($this->getAllPhotos());
    }
} 