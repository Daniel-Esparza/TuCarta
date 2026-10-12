<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['usuario_id', 'permiso_id', 'asignado_por', 'negocio_id'])]
class UsuarioPermiso extends Model
{
    protected $table = 'usuario_permisos';

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class);
    }

    public function permiso(): BelongsTo
    {
        return $this->belongsTo(Permiso::class);
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }

    public function asignadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'asignado_por');
    }
}
