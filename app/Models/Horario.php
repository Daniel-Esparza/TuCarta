<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use PerteneceANegocio;

    protected $fillable = ['negocio_id', 'dia_semana', 'hora_apertura', 'hora_cierre'];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }
}
