<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    private const INTENTOS_PERMITIDOS = 5;

    private const SEGUNDOS_DE_BLOQUEO = 300;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Escribe tu correo.',
            'email.email' => 'Escribe un correo válido.',
            'password.required' => 'Escribe tu contraseña.',
        ];
    }

    /**
     * Intenta iniciar sesión. Solo entran cuentas activas.
     *
     * @throws ValidationException
     */
    public function autenticar(): void
    {
        $this->verificarQueNoEsteBloqueado();

        $credenciales = [
            'email' => $this->string('email')->lower()->toString(),
            'password' => $this->string('password')->toString(),
            'estado' => 'activo',
        ];

        if (! Auth::attempt($credenciales)) {
            RateLimiter::hit($this->llaveDeIntentos(), self::SEGUNDOS_DE_BLOQUEO);

            // Mismo mensaje exista o no el correo.
            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña son incorrectos.',
            ]);
        }

        RateLimiter::clear($this->llaveDeIntentos());
    }

    /**
     * @throws ValidationException
     */
    private function verificarQueNoEsteBloqueado(): void
    {
        if (! RateLimiter::tooManyAttempts($this->llaveDeIntentos(), self::INTENTOS_PERMITIDOS)) {
            return;
        }

        event(new Lockout($this));

        $minutos = (int) ceil(RateLimiter::availableIn($this->llaveDeIntentos()) / 60);

        throw ValidationException::withMessages([
            'email' => "Demasiados intentos fallidos. Intenta de nuevo en {$minutos} ".($minutos === 1 ? 'minuto.' : 'minutos.'),
        ]);
    }

    private function llaveDeIntentos(): string
    {
        return Str::transliterate($this->string('email')->lower().'|'.$this->ip());
    }
}
