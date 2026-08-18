<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Expires the dashboard unlock after a period of inactivity anywhere in TRACOM.
 *
 * The dashboard's own idle timer only runs while the dashboard is on screen. If
 * someone unlocks it, moves to Order History and walks away, that timer is gone
 * with the page and the unlock would survive until logout. This tracks the last
 * request on any page instead, so returning after a break prompts again.
 *
 * The previous timestamp is read before the new one is written, otherwise every
 * request would look like it had just happened and nothing would ever expire.
 */
class TrackUserActivity
{
    /**
     * Minutes of inactivity before the dashboard re-locks. Kept in step with
     * IDLE_TIMEOUT in dashboard.blade.php, which is 15 minutes.
     */
    private const IDLE_MINUTES = 15;

    public function handle(Request $request, Closure $next)
    {
        // Deliberately no auth()->check() here. That would load the user record
        // from MySQL on every request; this only needs session keys, and the
        // session driver is file-based, so as written this touches no database.
        // dashboard_unlocked only ever exists for a signed-in user anyway.
        $lastActivity = session('last_activity');

        if ($lastActivity && session('dashboard_unlocked')) {
            $idleFor = Carbon::parse($lastActivity)->diffInSeconds(now());

            if ($idleFor >= self::IDLE_MINUTES * 60) {
                session()->forget(['dashboard_unlocked', 'dashboard_unlocked_at']);
            }
        }

        session(['last_activity' => now()->toDateTimeString()]);

        return $next($request);
    }
}