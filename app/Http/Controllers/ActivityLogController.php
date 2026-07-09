<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a filterable list of all activity logs.
     * Access is restricted to superadmin via the route middleware (role:superadmin).
     */
    public function index(Request $request)
    {
        $query = ActivityLog::with('user')->latest();

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model')) {
            $query->where('subject_type', 'App\\Models\\' . $request->model);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('search')) {
            $term = $request->search;
            $query->where(function ($q) use ($term) {
                $q->where('subject_label', 'like', "%{$term}%")
                  ->orWhere('description', 'like', "%{$term}%")
                  ->orWhere('user_name', 'like', "%{$term}%");
            });
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(30)->withQueryString();

        $models = ActivityLog::query()
            ->selectRaw('DISTINCT subject_type')
            ->pluck('subject_type')
            ->filter()
            ->map(fn ($t) => class_basename($t))
            ->values();

        $users = \App\Models\User::orderBy('username')->get(['id', 'username']);

        return view('logs.index', compact('logs', 'models', 'users'));
    }

    /**
     * Show a single log entry's full detail (including the change diff).
     */
    public function show(ActivityLog $activityLog)
    {
        $activityLog->load('user');
        return view('logs.show', compact('activityLog'));
    }
}
