<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OpcionController extends Controller
{
    public function store(Request $request, GrupoOpcion $grupo): RedirectResponse
    {
        abort_unless($grupo->negocio_id === auth()->user()->negocio_id, 404);

        $datos = $request->validateWithBag('opcion', $this->reglas(), $this->mensajes());

        $grupo->opciones()->create($datos + [
            'negocio_id' => $grupo->negocio_id,
            'orden' => ($grupo->opciones()->max('orden') ?? -1) + 1,
        ]);

        return $this->volverAlGrupo($grupo, 'Opción agregada.');
    }

    public function update(Request $request, Opcion $opcion): RedirectResponse
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $opcion->update($request->validateWithBag('opcion', $this->reglas(), $this->mensajes()));

        return $this->volverAlGrupo($opcion->grupo, 'Opción actualizada.');
    }

    public function destroy(Opcion $opcion): RedirectResponse
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $grupo = $opcion->grupo;
        $opcion->delete();

        return $this->volverAlGrupo($grupo, 'Opción eliminada.');
    }

    private function volverAlGrupo(GrupoOpcion $grupo, string $mensaje): RedirectResponse
    {
        return redirect()->route('grupos.index', $grupo->item_id)
            ->withFragment('grupo-'.$grupo->id)
            ->with('exito', $mensaje);
    }

    private function reglas(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:100'],
            'incremento_precio' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la opción.',
            'nombre.max' => 'El nombre no puede pasar de 100 caracteres.',
            'incremento_precio.required' => 'Escribe el precio extra. Puede ser 0.',
            'incremento_precio.numeric' => 'El precio extra debe ser un número.',
            'incremento_precio.min' => 'El precio extra no puede ser negativo.',
            'incremento_precio.max' => 'El precio extra es demasiado grande.',
        ];
    }
}
