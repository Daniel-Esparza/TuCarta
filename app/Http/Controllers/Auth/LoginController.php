<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    /** Intentos fallidos permitidos antes de bloquear. */
    private const MAX_INTENTOS = 5;

    /** Duracion del bloqueo (y ventana de conteo) en segundos: 5 minutos. */
    private const SEGUNDOS_BLOQUEO = 300;

    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'Escribe un correo electrónico válido.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        $clave = Str::transliterate(Str::lower($datos['email']).'|'.$request->ip());
        $intentos = 'login-intentos|'.$clave;
        $bloqueo = 'login-bloqueo|'.$clave;

        $this->avisarSiEstaBloqueado($bloqueo);

        // TODO (US-32): si las columnas de la tabla de usuarios cambian de nombre, se ajusta aqui.
        $credenciales = [
            'email' => $datos['email'],
            'password' => $datos['password'],
        ];

        if (! Auth::attempt($credenciales)) {
            RateLimiter::hit($intentos, self::SEGUNDOS_BLOQUEO);

            if (RateLimiter::attempts($intentos) >= self::MAX_INTENTOS) {
                RateLimiter::clear($intentos);
                RateLimiter::hit($bloqueo, self::SEGUNDOS_BLOQUEO);
                $this->avisarSiEstaBloqueado($bloqueo);
            }

            // Mensaje general: no revela si el correo existe.
            throw ValidationException::withMessages([
                'email' => 'Las credenciales no son correctas.',
            ]);
        }

        RateLimiter::clear($intentos);
        $request->session()->regenerate();

        // TODO (US-32): redirigir segun el rol (administrador, restaurante, cliente).
        return redirect()->intended(route('panel'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function avisarSiEstaBloqueado(string $bloqueo): void
    {
        if (! RateLimiter::tooManyAttempts($bloqueo, 1)) {
            return;
        }

        $minutos = (int) ceil(RateLimiter::availableIn($bloqueo) / 60);

        throw ValidationException::withMessages([
            'email' => 'Demasiados intentos fallidos. Intenta de nuevo en '
                .$minutos.($minutos === 1 ? ' minuto.' : ' minutos.'),
        ]);
    }
}
