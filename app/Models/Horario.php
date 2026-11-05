<?php

namespace App\Models;

use App\Models\Concerns\PerteneceANegocio;
use Illuminate\Database\Eloquent\Model;

class Horario extends Model
{
    use PerteneceANegocio;

    /** Fin de día: un rango que cruza la medianoche se parte aquí. */
    public const FIN_DE_DIA = '23:59:59';

    protected $fillable = ['negocio_id', 'dia_semana', 'hora_apertura', 'hora_cierre'];

    public function negocio()
    {
        return $this->belongsTo(Negocio::class, 'negocio_id');
    }

    /**
     * ¿El rango [apertura, cierre) choca con otro del mismo negocio y día?
     * Los rangos que solo se tocan en un extremo (18:00 y 18:00) no chocan.
     */
    public static function seTraslapa(int $negocioId, int $dia, string $apertura, string $cierre): bool
    {
        return static::where('negocio_id', $negocioId)
            ->where('dia_semana', $dia)
            ->where('hora_apertura', '<', $cierre)
            ->where('hora_cierre', '>', $apertura)
            ->exists();
    }
}