<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['negocio_id', 'nombre', 'orden', 'activo'];

    public function restaurante()
    {
        return $this->belongsTo(Restaurante::class, 'negocio_id');
    }

    public function items()
    {
        return $this->hasMany(Platillo::class)->orderBy('nombre');
    }
}
