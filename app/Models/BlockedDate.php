<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class BlockedDate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'blocked_date',
        'reason',
        'type',
        'is_active',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'blocked_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this blocked date.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if a specific date is blocked.
     *
     * @param string|Carbon $date
     * @return bool
     */
    public static function isDateBlocked($date): bool
    {
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }

        return self::where('blocked_date', $date->format('Y-m-d'))
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Get all active blocked dates.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveBlockedDates()
    {
        return self::where('is_active', true)
            ->orderBy('blocked_date')
            ->get();
    }

    /**
     * Get blocked dates as array of date strings for frontend.
     *
     * @return array
     */
    public static function getBlockedDatesArray(): array
    {
        return self::where('is_active', true)
            ->pluck('blocked_date')
            ->map(function ($date) {
                return Carbon::parse($date)->format('Y-m-d');
            })
            ->toArray();
    }

    /**
     * Get blocked dates with reasons for display.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getBlockedDatesWithReasons()
    {
        return self::where('is_active', true)
            ->where('blocked_date', '>=', now()->format('Y-m-d'))
            ->orderBy('blocked_date')
            ->get()
            ->map(function ($blockedDate) {
                return [
                    'date' => $blockedDate->blocked_date->format('Y-m-d'),
                    'formatted_date' => $blockedDate->blocked_date->format('d/m/Y'),
                    'reason' => $blockedDate->reason ?: ucfirst($blockedDate->type),
                    'type' => $blockedDate->type,
                ];
            });
    }

    /**
     * Scope to get future blocked dates only.
     */
    public function scopeFuture($query)
    {
        return $query->where('blocked_date', '>=', now()->format('Y-m-d'));
    }

    /**
     * Scope to get active blocked dates only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
} 