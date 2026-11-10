<?php

namespace App\Services;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CopiarOpciones
{
    /**
     * Copia los grupos, con sus opciones, a cada producto indicado.
     * Si el producto ya tiene un grupo con el mismo nombre, se deja igual, salvo que se pida reemplazarlo.
     *
     * @param  Collection<int, GrupoOpcion>  $grupos  con la relación opciones cargada
     * @param  array<int, int>  $productoIds
     * @return array{copiados: int, omitidos: int}
     */
    public function copiar(Collection $grupos, array $productoIds, bool $reemplazar = false): array
    {
        return DB::transaction(function () use ($grupos, $productoIds, $reemplazar) {
            $existentes = GrupoOpcion::whereIn('item_id', $productoIds)
                ->get(['id', 'item_id', 'nombre'])
                ->groupBy(fn ($grupo) => $this->clave($grupo->item_id, $grupo->nombre));

            $copiados = 0;
            $omitidos = 0;
            $opciones = [];
            $ahora = now();

            foreach ($productoIds as $productoId) {
                foreach ($grupos as $grupo) {
                    $repetidos = $existentes->get($this->clave($productoId, $grupo->nombre));

                    if ($repetidos && ! $reemplazar) {
                        $omitidos++;

                        continue;
                    }

                    if ($repetidos) {
                        GrupoOpcion::whereIn('id', $repetidos->pluck('id'))->delete();
                    }

                    $copia = GrupoOpcion::create($grupo->only([
                        'negocio_id', 'nombre', 'es_multiple', 'es_obligatorio',
                        'min_selecciones', 'max_selecciones', 'orden',
                    ]) + ['item_id' => $productoId]);

                    $copiados++;

                    foreach ($grupo->opciones as $opcion) {
                        $opciones[] = $opcion->only(['negocio_id', 'imagen', 'nombre', 'incremento_precio', 'orden']) + [
                            'grupo_opcion_id' => $copia->id,
                            'created_at' => $ahora,
                            'updated_at' => $ahora,
                        ];
                    }
                }
            }

            foreach (array_chunk($opciones, 500) as $lote) {
                Opcion::insert($lote);
            }

            return ['copiados' => $copiados, 'omitidos' => $omitidos];
        });
    }

    private function clave(int $productoId, string $nombre): string
    {
        return $productoId.'|'.mb_strtolower($nombre);
    }
}
