<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Restaurante extends Model
{
    protected $fillable = [
        'nombre', 'descripcion', 'logo', 'color_principal',
        'direccion', 'telefono', 'zona_horaria', 'cerrado_temporal',
    ];

    public function categorias()
    {
        return $this->hasMany(Categoria::class, 'negocio_id')->orderBy('orden');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'negocio_id');
    }
}