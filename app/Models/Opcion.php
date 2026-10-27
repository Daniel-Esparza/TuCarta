<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class Opcion extends Model
{
    use PerteneceANegocio;

    protected $table = 'opciones';

    protected $fillable = ['grupo_opcion_id', 'nombre', 'incremento_precio', 'orden', 'negocio_id', 'imagen'];

    public function grupo()
    {
        return $this->belongsTo(GrupoOpcion::class, 'grupo_opcion_id');
    }
}
