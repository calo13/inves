<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class AuthController extends Controller
{
    public function login(Request $r)
    {
        $v = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $key = 'login:'.mb_strtolower($v['email']).'|'.$r->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors(['email' => 'Demasiados intentos. Espere un minuto.']);
        }if (! Auth::attempt($v)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'Correo o contraseña incorrectos.'])->onlyInput('email');
        }RateLimiter::clear($key);
        $r->session()->regenerate();

        return redirect()->intended('/');
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return redirect('/ingresar');
    }

    public function password(Request $r)
    {
        $v = $r->validate(['current_password' => 'required|current_password', 'password' => 'required|confirmed|min:12']);
        $r->user()->update(['password' => $v['password']]);
        $r->session()->regenerate();

        return back()->with('status', 'Contraseña actualizada.');
    }
}
