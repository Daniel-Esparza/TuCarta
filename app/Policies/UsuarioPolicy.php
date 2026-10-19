<?php

namespace App\Policies;

use App\Models\Usuario;

class UsuarioPolicy
{
    // Solo el administrador con gestionar_moderadores global toca cuentas de administrador.
    private function puedeGestionarCuentas(Usuario $usuario): bool
    {
        return $usuario->esAdministrador()
            && $usuario->tienePermisoGlobal('gestionar_moderadores');
    }

    public function create(Usuario $usuario): bool
    {
        return $this->puedeGestionarCuentas($usuario);
    }

    public function update(Usuario $usuario, Usuario $objetivo): bool
    {
        return $objetivo->esAdministrador() && $this->puedeGestionarCuentas($usuario);
    }

    public function delete(Usuario $usuario, Usuario $objetivo): bool
    {
        return $this->update($usuario, $objetivo);
    }
}