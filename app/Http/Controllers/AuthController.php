<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('ui.invalid_login')])->onlyInput('email');
        }

        if (Auth::user()->isAdmin()) {
            $this->logoutSession($request);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors(['email' => __('ui.admin_login_only')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('account.dashboard'));
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function adminLoginForm(Request $request, AdminController $adminController)
    {
        if (Auth::check() && Auth::user()->isAdmin()) {
            return $adminController->dashboard();
        }

        if (Auth::check()) {
            $this->logoutSession($request);
        }

        return view('auth.admin-login');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => __('ui.invalid_login')])->onlyInput('email');
        }

        if (! Auth::user()->isAdmin()) {
            $this->logoutSession($request);

            return back()
                ->withErrors(['email' => __('ui.admin_login_required')])
                ->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard'));
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create($data + ['role' => 'user', 'country' => 'Moldova']);
        Auth::login($user);

        return redirect()->route('account.dashboard');
    }

    public function logout(Request $request)
    {
        $this->logoutSession($request);

        return redirect()->route('home');
    }

    private function logoutSession(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
