<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * Display the user management page.
     *
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $isSuperadmin = auth()->user()->role === 'superadmin';

        $query = User::query();

        // Everyone can reach User Management, but only a superadmin sees anyone
        // other than themselves. Scoping the query rather than hiding cards in
        // the view means a hand-typed ?id= cannot surface another account.
        if (!$isSuperadmin) {
            $query->where('id', auth()->id());
        }
        
        // Handle search functionality
        if ($isSuperadmin && $request->has('search') && !empty($request->search)) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                  ->orWhere('last_name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%")
                  ->orWhere('username', 'like', "%{$searchTerm}%")
                  ->orWhere('designation', 'like', "%{$searchTerm}%")
                  ->orWhere('department', 'like', "%{$searchTerm}%");
            });
        }
        
        // Handle department filter
        if ($isSuperadmin && $request->has('department') && !empty($request->department)) {
            $query->where('department', $request->department);
        }
        
        // Get all departments for filter dropdown
        $departments = User::select('department')
            ->whereNotNull('department')
            ->distinct()
            ->orderBy('department')
            ->pluck('department');
        
        // Paginate results
        $users = $query->paginate(8)->withQueryString();

        // Resolve the modal targets here instead of in the view. The view used
        // to call User::find(request('id')) directly, which happily returned
        // anybody's record regardless of who was asking.
        $editUser = null;
        $deleteUser = null;

        if ($request->get('modal') === 'edit') {
            $editUser = $isSuperadmin
                ? User::find($request->get('id'))
                : auth()->user();
        }

        // Deleting is superadmin-only, and never your own account.
        if ($request->get('modal') === 'delete' && $isSuperadmin) {
            $deleteUser = User::find($request->get('id'));
            if ($deleteUser && $deleteUser->id === auth()->id()) {
                $deleteUser = null;
            }
        }

        $canAddUser = $isSuperadmin;
        
        return view('settings.user', compact(
            'users', 'departments', 'editUser', 'deleteUser', 'isSuperadmin', 'canAddUser'
        ));
    }

    /**
     * Store a newly created user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        // Creating accounts is superadmin-only. Checked here rather than only
        // hiding the button, so the POST route is closed too.
        if (auth()->user()->role !== 'superadmin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only a superadmin can add users.'
                ], 403);
            }
            return redirect()->route('users.index')
                ->with('error', 'Only a superadmin can add users.');
        }

        try {
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'designation' => 'required|string|max:100',
                'role' => 'nullable|string|max:50',
                'department' => 'nullable|string|max:100',
            ]);

            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'designation' => $request->designation,
                'role' => $request->role ?? 'user',
                'department' => $request->department,
            ]);

            // If this is an AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User created successfully',
                    'user' => $user
                ]);
            }

            // For normal form submission, redirect back with success message
            return redirect()->route('users.index')->with('success', 'User created successfully');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create user: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to create user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(User $user)
    {
        try {
            // Same rule as the page itself: no peeking at other accounts.
            if (auth()->user()->role !== 'superadmin' && $user->id !== auth()->id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You can only view your own account.'
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'user' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch user: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        try {
            // If user was not found via route model binding but user_id was provided
            if (empty($user->id) && $request->filled('user_id')) {
                $user = User::findOrFail($request->user_id);
            }

            $isSuperadmin = auth()->user()->role === 'superadmin';

            // Anyone below superadmin may only edit themselves. Without this a
            // hand-crafted POST to /users/{id}/update would edit any account.
            if (!$isSuperadmin && $user->id !== auth()->id()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You can only edit your own account.'
                    ], 403);
                }
                return redirect()->route('users.index')
                    ->with('error', 'You can only edit your own account.');
            }
            
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'username' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'email' => [
                    'required',
                    'string',
                    'email',
                    'max:255',
                    Rule::unique('users')->ignore($user->id),
                ],
                'designation' => 'required|string|max:100',
                'role' => 'nullable|string|max:50',
                'department' => 'nullable|string|max:100',
                'password' => 'nullable|string|min:8',
            ]);

            $userData = [
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'username' => $request->username,
                'email' => $request->email,
                'designation' => $request->designation,
            ];

            // Role and department are superadmin-only. For everyone else the
            // posted values are ignored entirely, so submitting role=superadmin
            // by hand changes nothing.
            if ($isSuperadmin) {
                $userData['role'] = $request->role ?? 'user';
                $userData['department'] = $request->department;
            }

            // Only update password if provided
            if ($request->filled('password')) {
                $userData['password'] = Hash::make($request->password);
            }

            $user->update($userData);

            // If this is an AJAX request, return JSON response
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User updated successfully',
                    'user' => $user
                ]);
            }

            // For normal form submission, redirect back with success message
            return redirect()->route('users.index')->with('success', 'User updated successfully');
        } catch (ValidationException $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }
            return redirect()->back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to update user: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to update user: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified user.
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, User $user)
    {
        // Deleting accounts is superadmin-only.
        if (auth()->user()->role !== 'superadmin') {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Only a superadmin can delete users.'
                ], 403);
            }
            return redirect()->route('users.index')
                ->with('error', 'Only a superadmin can delete users.');
        }

        try {
            // If user was not found via route model binding but user_id was provided
            if (empty($user->id) && $request->filled('user_id')) {
                $user = User::findOrFail($request->user_id);
            }
            
            // Prevent deleting yourself
            if (auth()->id() === $user->id) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'You cannot delete your own account'
                    ], 403);
                }
                return redirect()->back()->with('error', 'You cannot delete your own account');
            }
            
            $user->delete();

            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'User deleted successfully'
                ]);
            }
            
            return redirect()->route('users.index')->with('success', 'User deleted successfully');
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to delete user: ' . $e->getMessage()
                ], 500);
            }
            return redirect()->back()->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
