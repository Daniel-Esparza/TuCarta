<?php

namespace App\Enums;

// Moderador y empleado no son roles aparte, se distinguen por sus permisos.
enum Rol: string
{
    case Administrador = 'administrador';
    case Negocio = 'negocio';
    case Cliente = 'cliente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Negocio => 'Negocio',
            self::Cliente => 'Cliente',
        };
    }
}
