<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use Illuminate\Http\Request;

class OpcionController extends Controller
{
    public function store(Request $request, GrupoOpcion $grupo)
    {
        abort_unless($grupo->negocio_id === auth()->user()->negocio_id, 404);

        $datos = $this->validarOpcion($request);

        $grupo->opciones()->create($datos + ['negocio_id' => $grupo->negocio_id]);

        return back()->with('ok', 'Opción creada.');
    }

    public function update(Request $request, Opcion $opcion)
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $opcion->update($this->validarOpcion($request));

        return back()->with('ok', 'Opción actualizada.');
    }

    public function destroy(Opcion $opcion)
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $opcion->delete();

        return back()->with('ok', 'Opción eliminada.');
    }

    private function validarOpcion(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'incremento_precio' => ['required', 'numeric', 'min:0'],
        ]);

        return [
            'nombre' => $datos['nombre'],
            'incremento_precio' => $datos['incremento_precio'],
        ];
    }
}
