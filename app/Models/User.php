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
     * is deliberately read-only: it may open and print a COA but never save,
     * switch template or upload artwork.
     */
    public const DEPT_QUALITY_CONTROL   = 'Quality Control';
    public const DEPT_QUALITY_ASSURANCE = 'Quality Assurance';

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

    /**
     * May open the COA editor, print and download.
     */
    public function canViewCoa(): bool
    {
        return $this->role === 'superadmin'
            || $this->isQualityControl()
            || $this->isQualityAssurance();
    }

    /**
     * May save fields, switch template, upload morphology or attach a COA PDF.
     */
    public function canEditCoa(): bool
    {
        return $this->role === 'superadmin'
            || $this->isQualityControl();
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
