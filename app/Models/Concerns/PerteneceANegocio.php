<?php

namespace App\Models\Concerns;

use App\Models\Negocio;
use App\Models\Scopes\NegocioScope;
use App\Support\NegocioActual;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Para los modelos con columna negocio_id. Filtra por el negocio actual y,
 * al crear, toma el negocio_id del negocio actual, no del formulario.
 */
trait PerteneceANegocio
{
    public static function bootPerteneceANegocio(): void
    {
        static::addGlobalScope(new NegocioScope);

        static::creating(function (Model $model) {
            $negocioId = app(NegocioActual::class)->id();

            if ($negocioId !== null) {
                $model->negocio_id = $negocioId;
            }
        });
    }

    public function negocio(): BelongsTo
    {
        return $this->belongsTo(Negocio::class);
    }
}
