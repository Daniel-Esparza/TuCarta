<?php

namespace App\Enums;

// Ciclo de vida de un negocio. Solo un negocio activo abre su dirección.
enum EstadoNegocio: string
{
    case Activo = 'activo';
    case Suspendido = 'suspendido';
    case Pendiente = 'pendiente';
    case Rechazado = 'rechazado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Suspendido => 'Suspendido',
            self::Pendiente => 'Pendiente',
            self::Rechazado => 'Rechazado',
        };
    }
}
