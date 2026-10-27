<?php

namespace App\Support;

use App\Models\Negocio;

/**
 * Negocio de la petición actual. Lo fija el middleware del subdominio y lo
 * usan las dos capas de separación: el filtro de Eloquent y el RLS.
 */
class NegocioActual
{
    private ?Negocio $negocio = null;

    private bool $verTodo = false;

    public function establecer(Negocio $negocio): void
    {
        $this->negocio = $negocio;
        $this->verTodo = false;

        SeguridadPorFilas::fijarNegocio($negocio->id);
    }

    // Solo para el panel del administrador.
    public function verTodo(): void
    {
        $this->negocio = null;
        $this->verTodo = true;

        SeguridadPorFilas::verTodo();
    }

    public function limpiar(): void
    {
        $this->negocio = null;
        $this->verTodo = false;
    }

    public function negocio(): ?Negocio
    {
        return $this->negocio;
    }

    public function id(): ?int
    {
        return $this->negocio?->id;
    }

    public function puedeVerTodo(): bool
    {
        return $this->verTodo;
    }
}
