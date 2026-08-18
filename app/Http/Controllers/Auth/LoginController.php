<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     * This will be dynamically determined based on user department.
     *
     * @var string
     */
    protected $redirectTo = '/calendar';

    /**
     * Get the post-login redirect path based on user department.
     *
     * @return string
     */
    public function redirectTo()
    {
        // All users (admin, superadmin, and every department) land on the calendar/dashboard.
        return '/calendar';
    }

    /**
     * Mark the session unlocked immediately after signing in.
     *
     * The idle lock asks for the same password the user just typed, so without
     * this they would be challenged again the moment they land on a page.
     * TrackUserActivity expires this after the idle interval as normal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return void
     */
    protected function authenticated($request, $user)
    {
        session([
            'dashboard_unlocked'    => true,
            'dashboard_unlocked_at' => now()->toDateTimeString(),
            'last_activity'         => now()->toDateTimeString(),
        ]);
    }

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
        $this->middleware('auth')->only('logout');
    }

    /**
     * Show the application's login form.
     *
     * @return \Illuminate\Http\Response
     */
    public function showLoginForm()
    {
        return response()
            ->view('auth.login')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }
}