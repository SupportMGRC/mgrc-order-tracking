<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckDepartmentPermissions
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Check permissions based on department
        switch ($permission) {
            case 'edit-batch-number':
                // Only Cell Lab department can edit batch document number
                if ($user->department !== 'Cell Lab' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Cell Lab department can edit batch document numbers.');
                }
                break;

            case 'edit-qc-document':
                // Only Quality Control department can insert QC document number
                if (!$user->isQualityControl() && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Quality Control department can edit QC document numbers.');
                }
                break;

            case 'mark-ready':
                // Only Quality Control and Cell Lab departments can mark orders as ready
                if (!$user->isQualityControl() && $user->department !== 'Cell Lab' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Quality Control or Cell Lab departments can mark orders as ready.');
                }
                break;

            case 'mark-delivered':
                // Only Admin and Dispatcher departments can mark orders as delivered
                if ($user->department !== 'Admin & Human Resource' && $user->department !== 'Dispatcher' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Admin or Dispatcher departments can mark orders as delivered.');
                }
                break;

            case 'view-new-order':
                // Only Medical Affairs and Business Development departments can view the new order page
                if ($user->department !== 'Medical Affairs' && $user->department !== 'Business Development' && $user->role !== 'superadmin' && $user->role !== 'admin') {
                    return redirect()->route('dashboard')->with('error', 'Only Medical Affairs, Business Development departments, and Administrators can access the new order page.');
                }
                break;
        }

        return $next($request);
    }
}