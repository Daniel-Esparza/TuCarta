<?php

namespace App\Models;

use Database\Factories\NegocioFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'nombre',
    'subdominio',
    'telefono_whatsapp',
    'correo_contacto',
    'descripcion',
    'logo_path',
    'direccion',
    'categoria',
    'zona_horaria',
    'cerrado_temporal',
    'personalizacion',
    'estado',
])]
class Negocio extends Model
{
    /** @use HasFactory<NegocioFactory> */
    use HasFactory;

    protected $table = 'negocios';

    protected function casts(): array
    {
        return [
            'cerrado_temporal' => 'boolean',
            'personalizacion' => 'array',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class);
    }
}
