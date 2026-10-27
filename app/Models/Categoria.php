<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use PerteneceANegocio;

    protected $fillable = ['negocio_id', 'nombre', 'orden', 'activo'];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('nombre');
    }
}
