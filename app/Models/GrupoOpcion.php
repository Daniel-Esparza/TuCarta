<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class GrupoOpcion extends Model
{
    use PerteneceANegocio;

    protected $table = 'grupos_opciones';

    protected $fillable = [
        'item_id', 'nombre', 'es_multiple', 'es_obligatorio',
        'min_selecciones', 'max_selecciones', 'orden', 'negocio_id',
    ];

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    public function opciones()
    {
        return $this->hasMany(Opcion::class, 'grupo_opcion_id')->orderBy('orden');
    }
}
