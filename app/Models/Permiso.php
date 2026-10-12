<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['clave', 'descripcion', 'tipo_usuario'])]
class Permiso extends Model
{
    protected $table = 'permisos';

    public function asignaciones(): HasMany
    {
        return $this->hasMany(UsuarioPermiso::class);
    }
}
