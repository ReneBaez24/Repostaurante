<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        // Máximo 5 intentos por minuto por correo + IP
        $llave = Str::lower($credenciales['email']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($llave, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Intenta de nuevo en '.RateLimiter::availableIn($llave).' segundos.',
            ]);
        }

        if (! Auth::attempt($credenciales, $request->boolean('recordar'))) {
            RateLimiter::hit($llave);

            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no son correctos.',
            ]);
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();

        $destino = $request->user()->esAdmin() ? route('admin.dashboard') : route('overview');

        return redirect()->intended($destino);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
