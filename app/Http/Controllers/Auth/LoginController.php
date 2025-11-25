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
        $user = auth()->user();
        
        // Medical Affairs and Business Development users go to new order page
        if ($user->department === 'Medical Affairs' || $user->department === 'Business Development') {
            return '/neworder';
        }
        
        // All other users go to calendar/dashboard
        return '/calendar';
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
