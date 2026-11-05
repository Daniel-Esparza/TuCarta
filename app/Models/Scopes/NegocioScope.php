<?php

namespace App\Models\Scopes;

use App\Support\NegocioActual;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class NegocioScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $negocioActual = app(NegocioActual::class);

        if ($negocioActual->id() !== null && ! $negocioActual->puedeVerTodo()) {
            $builder->where($model->qualifyColumn('negocio_id'), $negocioActual->id());
        }
    }
}
