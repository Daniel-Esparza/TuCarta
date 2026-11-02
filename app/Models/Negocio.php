<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    public function categorias()
    {
        return $this->hasMany(Categoria::class, 'negocio_id')->orderBy('orden');
    }

    public function horarios()
    {
        return $this->hasMany(Horario::class, 'negocio_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'negocio_id');
    }

    /**
     * US-19a: ¿está dentro de su horario en este momento?
     * Usa la zona horaria del negocio y entiende los rangos que cruzan la
     * medianoche (guardados en dos filas). No mira el cierre temporal.
     */
    public function dentroDeHorario(?CarbonInterface $ahora = null): bool
    {
        $ahora = ($ahora ?? now())->copy()
            ->setTimezone($this->zona_horaria ?: 'America/Mexico_City');
        $hora = $ahora->format('H:i:s');

        return $this->horarios()
            ->where('dia_semana', $ahora->dayOfWeekIso)
            ->where('hora_apertura', '<=', $hora)
            ->where(function ($q) use ($hora) {
                $q->where('hora_cierre', '>', $hora)
                  ->orWhere('hora_cierre', Horario::FIN_DE_DIA);
            })
            ->exists();
    }

    /**
     * US-19a: la función que usa US-19b para bloquear pedidos.
     * Abierto = no está en cierre temporal Y está dentro de su horario.
     */
    public function estaAbierto(?CarbonInterface $ahora = null): bool
    {
        return ! $this->cerrado_temporal && $this->dentroDeHorario($ahora);
    }
}