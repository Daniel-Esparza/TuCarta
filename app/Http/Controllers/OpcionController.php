<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Opcion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OpcionController extends Controller
{
    public function index(GrupoOpcion $grupo): View
    {
        abort_unless($grupo->negocio_id === auth()->user()->negocio_id, 404);

        return view('opciones.grupo', ['grupo' => $grupo->load('item', 'opciones')]);
    }

    public function store(Request $request, GrupoOpcion $grupo): RedirectResponse
    {
        abort_unless($grupo->negocio_id === auth()->user()->negocio_id, 404);

        $datos = $request->validate($this->reglas(), $this->mensajes());

        $grupo->opciones()->create($datos + [
            'negocio_id' => $grupo->negocio_id,
            'orden' => ($grupo->opciones()->max('orden') ?? -1) + 1,
        ]);

        return redirect()->route('grupos.show', $grupo)->with('exito', 'Opción creada.');
    }

    public function update(Request $request, Opcion $opcion): RedirectResponse
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $opcion->update($request->validateWithBag('editar', $this->reglas(), $this->mensajes()));

        return redirect()->route('grupos.show', $opcion->grupo_opcion_id)->with('exito', 'Opción actualizada.');
    }

    public function destroy(Opcion $opcion): RedirectResponse
    {
        abort_unless($opcion->negocio_id === auth()->user()->negocio_id, 404);

        $opcion->delete();

        return redirect()->route('grupos.show', $opcion->grupo_opcion_id)->with('exito', 'Opción eliminada.');
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
