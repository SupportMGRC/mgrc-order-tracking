<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Departments with a stake in the COA workflow.
     *
     * Quality Control produces certificates. Quality Assurance checks them and
     * is read-only: it may open, print and download a COA but never submit,
     * switch template or upload artwork. Everyone else with order access may
     * view and download.
     */
    public const DEPT_QUALITY_CONTROL   = 'Quality Control';
    public const DEPT_QUALITY_ASSURANCE = 'Quality Assurance';

    /**
     * Departments that only work on pickups. They have no access to New Order,
     * Order History, order details or Blocked Dates. Their admins can still
     * open Product and Customer; their users cannot open Customer.
     * superadmin is never restricted, whatever department it is in.
     */
    public const PICKUP_ONLY_DEPARTMENTS = ['Genomics'];

    /**
     * Case-insensitive department match, so a row saved with different casing
     * does not silently lose access.
     */
    public function isDepartment(string $name): bool
    {
        return strcasecmp((string) $this->department, $name) === 0;
    }

    public function isQualityControl(): bool
    {
        return $this->isDepartment(self::DEPT_QUALITY_CONTROL);
    }

    public function isQualityAssurance(): bool
    {
        return $this->isDepartment(self::DEPT_QUALITY_ASSURANCE);
    }

    public function isPickupOnly(): bool
    {
        if ($this->role === 'superadmin') {
            return false;
        }

        foreach (self::PICKUP_ONLY_DEPARTMENTS as $department) {
            if ($this->isDepartment($department)) {
                return true;
            }
        }

        return false;
    }

    /** New Order, Order History, order details, PRF and COA pages. */
    public function canAccessOrders(): bool
    {
        return !$this->isPickupOnly();
    }

    /** Customer pages: everyone, except users (not admins) in a pickup-only department. */
    public function canAccessCustomers(): bool
    {
        return !$this->isPickupOnly() || $this->role === 'admin';
    }

    /** Blocked Dates: admin and superadmin, but not admins in a pickup-only department. */
    public function canManageBlockedDates(): bool
    {
        return in_array($this->role, ['admin', 'superadmin'], true) && !$this->isPickupOnly();
    }

    /**
     * May open a COA read-only. Everyone who can open orders: MA and BD are
     * limited to their own orders by OrderController, and pickup-only
     * departments (Genomics) have no order access at all.
     */
    public function canViewCoa(): bool
    {
        return $this->canAccessOrders();
    }

    /**
     * May fill in and submit a COA, switch template or upload morphology.
     * Superadmin keeps this for support, but a submitted COA is locked for
     * everyone, superadmin included.
     */
    public function canEditCoa(): bool
    {
        return $this->role === 'superadmin'
            || $this->isQualityControl();
    }

    /**
     * May print a COA. Printing produces the official copy on certificate
     * paper, so it stays with Quality Control, Quality Assurance and
     * superadmin. Everyone else downloads a softcopy.
     */
    public function canPrintCoa(): bool
    {
        return $this->role === 'superadmin'
            || $this->isQualityControl()
            || $this->isQualityAssurance();
    }

    /**
     * May download a COA that Quality Control has not submitted yet. Others
     * only get the download once it is submitted, so a draft cannot reach a
     * client.
     */
    public function canDownloadDraftCoa(): bool
    {
        return $this->canEditCoa();
    }

    /**
     * May ask for a submitted COA to be unlocked. Quality Control only,
     * including whoever submitted it.
     */
    public function canRequestCoaEdit(): bool
    {
        return $this->isQualityControl();
    }

    /**
     * May approve or reject an unlock request: the COA approver (QC HOD),
     * set by superadmin in User Management, or a superadmin as backup.
     */
    public function canApproveCoaEdit(): bool
    {
        return $this->role === 'superadmin'
            || (bool) $this->coa_approver;
    }

    /**
     * First and last name, as printed on a COA. Falls back to the username
     * for an account with no name filled in.
     */
    public function fullName(): string
    {
        $name = trim(preg_replace('/\s+/', ' ', ($this->first_name ?? '') . ' ' . ($this->last_name ?? '')));

        return $name !== '' ? $name : (string) $this->username;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'email',
        'password',
        'role',
        'department',
        'designation',
        'coa_approver',
        'receive_new_order_emails',
        'receive_order_ready_emails',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'receive_new_order_emails' => 'boolean',
        'receive_order_ready_emails' => 'boolean',
        'coa_approver' => 'boolean',
    ];

    /**
     * Get the customers associated with the user.
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'userID');
    }

    /**
     * Get the orders created by the user.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get the visits handled by the user.
     */
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    // Equipment-related methods removed
}
