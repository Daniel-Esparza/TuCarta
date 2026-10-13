<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class item extends Model
{
    protected $fillable = [
        'categoria_id', 'nombre', 'descripcion', 'precio',
        'imagen', 'miniatura', 'disponible_hoy', 'activo',
        'negocio_id'
    ];

    protected $casts = ['precio' => 'decimal:2'];

    public function categoria()
    {
        return $this->belongsTo(Categoria::class);
    }

    public function gruposOpciones()
    {
        return $this->hasMany(GrupoOpcion::class)->orderBy('orden');
    }
}
