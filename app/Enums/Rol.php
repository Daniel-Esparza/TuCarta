<?php

namespace App\Enums;

/**
 * Tipos base de usuario (usuarios.rol). Moderador y empleado no son tipos
 * aparte: son administrador o negocio con menos permisos en usuario_permisos.
 */
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
