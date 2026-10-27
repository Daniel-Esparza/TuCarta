<?php

namespace App\Models;

use App\Enums\ClavePermiso;
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

    public function claveEnum(): ClavePermiso
    {
        return ClavePermiso::from($this->clave);
    }

    public static function deClave(ClavePermiso $clave): self
    {
        return static::where('clave', $clave->value)->firstOrFail();
    }
}
