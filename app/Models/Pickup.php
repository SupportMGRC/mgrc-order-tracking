<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pickup extends Model
{
    /**
     * A request for the despatcher to collect items (Blood Tube) from a
     * customer and bring them to MGRC. Kept separate from Order: no stock,
     * batch, COA or PRF applies.
     */

    public const STATUS_NEW        = 'new';
    public const STATUS_ON_THE_WAY = 'on_the_way';
    public const STATUS_PICKED_UP  = 'picked_up';
    public const STATUS_RECEIVED   = 'received';
    public const STATUS_CANCELLED  = 'cancelled';

    /** Display order matters: it drives the history tabs and the progress tracker. */
    public const STATUSES = [
        self::STATUS_NEW        => 'New',
        self::STATUS_ON_THE_WAY => 'On the Way',
        self::STATUS_PICKED_UP  => 'Picked Up',
        self::STATUS_RECEIVED   => 'Received',
        self::STATUS_CANCELLED  => 'Cancelled',
    ];

    /** Bootstrap colour per status, shared by history and details. */
    public const STATUS_BADGES = [
        self::STATUS_NEW        => 'light text-dark',
        self::STATUS_ON_THE_WAY => 'warning',
        self::STATUS_PICKED_UP  => 'primary',
        self::STATUS_RECEIVED   => 'success',
        self::STATUS_CANCELLED  => 'danger',
    ];

    protected $fillable = [
        'reference_no',
        'customer_id',
        'user_id',
        'requested_by',
        'contact_person',
        'contact_phone',
        'pickup_address',
        'pickup_date',
        'pickup_time',
        'time_sensitive',
        'remarks',
        'status',
    ];

    protected $casts = [
        'pickup_date'    => 'date',
        'pickup_time'    => 'datetime',
        'time_sensitive' => 'boolean',
        'on_the_way_at'  => 'datetime',
        'picked_up_at'   => 'datetime',
        'received_at'    => 'datetime',
        'cancelled_at'   => 'datetime',
        'pickup_photos'  => 'array',
    ];

    /** Department whose users take pickups (same as Mark as Delivered). */
    public const DESPATCH_DEPARTMENT = 'Dispatcher';

    /**
     * Department that takes the blood tubes in at MGRC and marks a pickup
     * Received. admin and superadmin can always do it as well.
     * CONFIRM WITH MANAGEMENT: change this list if it is not Cell Lab.
     */
    public const RECEIVING_DEPARTMENTS = ['Cell Lab'];

    /** Folder under storage/app/public and public/storage for pickup photos. */
    public const PHOTO_DIR = 'pickup_photos';

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(PickupItem::class);
    }

    public function despatcher()
    {
        return $this->belongsTo(User::class, 'despatcher_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function statusBadge(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }

    /**
     * PU-2026-0001. Built from the row id, so it is unique without a
     * separate counter. The number does not reset each year.
     */
    public static function makeReference(int $id, ?\DateTimeInterface $date = null): string
    {
        $year = ($date ?? now())->format('Y');

        return 'PU-' . $year . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
