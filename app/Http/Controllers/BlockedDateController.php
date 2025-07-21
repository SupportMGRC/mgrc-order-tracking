<?php

namespace App\Http\Controllers;

use App\Models\BlockedDate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class BlockedDateController extends Controller
{
    /**
     * Ensure only admin and superadmin can access these methods
     */
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!in_array(Auth::user()->role, ['admin', 'superadmin'])) {
                abort(403, 'Unauthorized access');
            }
            return $next($request);
        })->except(['api']);
    }

    /**
     * Display a listing of blocked dates.
     */
    public function index()
    {
        $blockedDates = BlockedDate::with('creator')
            ->orderBy('blocked_date', 'desc')
            ->paginate(15);
            
        return view('settings.blocked-dates', compact('blockedDates'));
    }

    /**
     * Store a newly created blocked date.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blocked_date' => 'required|date|after_or_equal:today|unique:blocked_dates,blocked_date',
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:holiday,maintenance,closure,other',
        ], [
            'blocked_date.required' => 'The date is required.',
            'blocked_date.after_or_equal' => 'Cannot block dates in the past.',
            'blocked_date.unique' => 'This date is already blocked.',
            'type.required' => 'Please select a type.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the errors and try again.');
        }

        BlockedDate::create([
            'blocked_date' => $request->blocked_date,
            'reason' => $request->reason,
            'type' => $request->type,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]);

        return redirect()->back()
            ->with('success', 'Date blocked successfully for ' . Carbon::parse($request->blocked_date)->format('d/m/Y'));
    }

    /**
     * Update the specified blocked date.
     */
    public function update(Request $request, BlockedDate $blockedDate)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'nullable|string|max:255',
            'type' => 'required|in:holiday,maintenance,closure,other',
            'is_active' => 'nullable|boolean', // Change to nullable
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Please fix the errors and try again.');
        }

        $updateData = [
            'reason' => $request->reason,
            'type' => $request->type,
        ];

        // Only update is_active if it's explicitly provided
        if ($request->has('is_active')) {
            $updateData['is_active'] = $request->boolean('is_active');
        }

        $blockedDate->update($updateData);

        $status = $request->boolean('is_active', $blockedDate->is_active) ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success', 'Blocked date ' . $status . ' successfully.');
    }

    /**
     * Remove the specified blocked date.
     */
    public function destroy(BlockedDate $blockedDate)
    {
        $date = $blockedDate->blocked_date->format('d/m/Y');
        $blockedDate->delete();

        return redirect()->back()
            ->with('success', "Blocked date {$date} deleted successfully.");
    }

    /**
     * Toggle the active status of a blocked date.
     */
    public function toggle(BlockedDate $blockedDate)
    {
        $blockedDate->update([
            'is_active' => !$blockedDate->is_active
        ]);

        $status = $blockedDate->is_active ? 'activated' : 'deactivated';
        return redirect()->back()
            ->with('success', 'Blocked date ' . $status . ' successfully.');
    }

    /**
     * Get blocked dates as JSON for API use.
     */
    public function api()
    {
        $blockedDates = BlockedDate::getBlockedDatesArray();
        return response()->json($blockedDates);
    }
} 