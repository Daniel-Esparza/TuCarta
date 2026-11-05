<?php

namespace App\Rules;

use App\Models\Negocio;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Regla única de la dirección web (subdominio) de un negocio. La usan el alta
 * del administrador (US-02a) y el auto-registro (US-26a).
 */
class DireccionWeb implements ValidationRule
{
    public const MINIMO = 3;

    public const MAXIMO = 30;

    public function __construct(private ?Negocio $ignorar = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('Escribe la dirección web de tu negocio.');

            return;
        }

        if (mb_strlen($value) < self::MINIMO) {
            $fail('La dirección web debe tener al menos '.self::MINIMO.' caracteres.');

            return;
        }

        if (mb_strlen($value) > self::MAXIMO) {
            $fail('La dirección web no puede pasar de '.self::MAXIMO.' caracteres.');

            return;
        }

        // Minúsculas, números y guion; sin guion al inicio ni al final.
        if (! preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $value)) {
            $fail('Usa solo minúsculas, números y guion, sin espacios.');

            return;
        }

        if (in_array($value, config('tucarta.reservados'), true)) {
            $fail('Esa dirección web no está disponible.');

            return;
        }

        // La tabla negocios no tiene RLS: la búsqueda ve todos los negocios
        // aunque no haya uno fijado en la petición.
        $repetida = Negocio::where('subdominio', $value)
            ->when($this->ignorar, fn ($consulta) => $consulta->whereKeyNot($this->ignorar->getKey()))
            ->exists();

        if ($repetida) {
            $fail('Esa dirección web ya está en uso.');
        }
    }
}
