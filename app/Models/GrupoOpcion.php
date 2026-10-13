<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoOpcion extends Model
{
    protected $table = 'grupos_opciones';

    protected $fillable = [
        'platillo_id', 'nombre', 'es_multiple', 'es_obligatorio',
        'min_selecciones', 'max_selecciones', 'orden',
    ];

    public function platillo()
    {
        return $this->belongsTo(Negocio::class);
    }

    public function opciones()
    {
        return $this->hasMany(Opcion::class, 'grupo_opcion_id')->orderBy('orden');
    }
}
