<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    use HasFactory;

    protected $fillable = ['negocio_id', 'nombre', 'orden'];

    public function items()
    {
        return $this->hasMany(Item::class)->orderBy('nombre');
    }

    public function scopeConProductos($query)
    {
        return $query->whereHas('items');
    }
}