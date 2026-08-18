<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }
        
        if ($user->role === 'superadmin') {
            return $next($request);
        }

        if (!in_array($user->role, $roles, true)) {
            // 403 rather than a redirect to the dashboard. A redirect looks like the
            // link was wrong or the page moved; this says plainly that the page
            // exists and access was refused. Matches BlockedDateController, which
            // already aborts, so every blocked page now behaves the same way.
            abort(403, 'Unauthorized access');
        }

        return $next($request);
    }
}