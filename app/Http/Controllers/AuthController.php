<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        return view('auth.login', ['next' => $request->query('next')]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __('ui.auth.failed')]);
        }
        if (! $request->user()->is_active) {
            Auth::logout();

            return back()->withErrors(['email' => 'This account has been deactivated.']);
        }
        $request->session()->regenerate();
        $next = $request->input('next');
        if (! $next && $request->user()->isAdmin()) {
            $next = route('admin.dashboard');
        }

        return redirect()->to($this->safeNext($next))->with('success', __('ui.auth.welcome_back', ['name' => explode(' ', $request->user()->name)[0]]));
    }

    public function showRegister(Request $request)
    {
        return view('auth.register', ['next' => $request->query('next')]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::min(6)],
        ]);
        $user = User::create($data);
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to($this->safeNext($request->input('next')))->with('success', __('ui.auth.studio_ready'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    private function safeNext(?string $next): string
    {
        if ($next && str_starts_with($next, '/') && ! str_starts_with($next, '//')) {
            return $next;
        }
        if ($next && str_starts_with($next, url('/'))) {
            return $next;
        }

        return route('home');
    }
}
