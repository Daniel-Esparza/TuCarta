<?php

namespace App\Policies;

use App\Enums\ClavePermiso;
use App\Models\Negocio;
use App\Models\Usuario;

class NegocioPolicy
{
    /**
     * Ver el listado de negocios: administrador con permiso global
     * o con permiso sobre al menos un negocio.
     */
    public function viewAny(Usuario $usuario): bool
    {
        return $usuario->esAdministrador()
            && ($usuario->tienePermisoGlobal(ClavePermiso::GestionarNegocios)
                || $usuario->negociosConPermiso(ClavePermiso::GestionarNegocios)->isNotEmpty());
    }

    /**
     * Ver un negocio: permiso global o permiso sobre ese negocio.
     */
    public function view(Usuario $usuario, Negocio $negocio): bool
    {
        return $usuario->esAdministrador()
            && $usuario->tienePermiso(ClavePermiso::GestionarNegocios, $negocio);
    }

    // US-27 solo cubre la consulta. Crear, editar y borrar negocios no se abre.
    public function create(Usuario $usuario): bool
    {
        return false;
    }

    public function update(Usuario $usuario, Negocio $negocio): bool
    {
        return false;
    }

    public function delete(Usuario $usuario, Negocio $negocio): bool
    {
        return false;
    }
}
