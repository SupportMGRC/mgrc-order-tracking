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
                // Only Quality department can insert QC document number
                if ($user->department !== 'Quality' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Quality department can edit QC document numbers.');
                }
                break;
                
            case 'mark-ready':
                // Only Quality and Cell Lab departments can mark orders as ready
                if ($user->department !== 'Quality' && $user->department !== 'Cell Lab' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Quality or Cell Lab departments can mark orders as ready.');
                }
                break;
                
            case 'mark-delivered':
                // Only Admin department can mark orders as delivered
                if ($user->department !== 'Admin & Human Resource' && $user->role !== 'admin' && $user->role !== 'superadmin') {
                    return redirect()->back()->with('error', 'Only Admin department can mark orders as delivered.');
                }
                break;
        }
        
        return $next($request);
    }
} 