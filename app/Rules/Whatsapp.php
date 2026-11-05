<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * WhatsApp en formato internacional: + lada del país y 10 dígitos.
 * Antes de validar se quitan espacios, guiones, puntos y paréntesis con normalizar().
 */
class Whatsapp implements ValidationRule
{
    public static function normalizar(mixed $valor): string
    {
        return preg_replace('/[\s\-().]/', '', (string) $valor);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^\+\d{11,13}$/', $value)) {
            $fail('Escribe el WhatsApp con lada y 10 dígitos, por ejemplo +524441234567.');
        }
    }
}
