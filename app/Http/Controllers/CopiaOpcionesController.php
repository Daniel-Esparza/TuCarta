<?php

namespace App\Http\Controllers;

use App\Models\GrupoOpcion;
use App\Models\Item;
use App\Services\CopiarOpciones;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CopiaOpcionesController extends Controller
{
    public function __construct(private CopiarOpciones $copiado) {}

    public function store(Request $request, Item $item): RedirectResponse
    {
        $negocioId = (int) $request->user()?->negocio_id;
        abort_unless((int) $item->negocio_id === $negocioId, 404, 'Producto no encontrado.');

        $datos = $request->validate([
            'grupos' => ['required', 'array'],
            'grupos.*' => ['integer'],
            'categorias' => ['nullable', 'array'],
            'categorias.*' => ['integer'],
            'productos' => ['nullable', 'array'],
            'productos.*' => ['integer'],
        ], [
            'grupos.required' => 'Elige al menos un grupo para copiar.',
        ]);

        $grupos = GrupoOpcion::where('item_id', $item->id)
            ->whereIn('id', $datos['grupos'])
            ->with('opciones')
            ->get();

        $destinos = Item::where('negocio_id', $negocioId)
            ->whereKeyNot($item->id)
            ->where(fn ($consulta) => $consulta
                ->whereIn('categoria_id', $datos['categorias'] ?? [])
                ->orWhereIn('id', $datos['productos'] ?? []))
            ->pluck('id')
            ->all();

        if ($destinos === []) {
            throw ValidationException::withMessages(['destinos' => 'Elige al menos una categoría o un producto.']);
        }

        $resultado = $this->copiado->copiar($grupos, $destinos, $request->boolean('reemplazar'));

        $mensaje = "Se copiaron {$resultado['copiados']} grupo(s) a ".count($destinos).' producto(s).';

        if ($resultado['omitidos'] > 0) {
            $mensaje .= " {$resultado['omitidos']} no se copiaron porque el producto ya tenía un grupo con ese nombre.";
        }

        return redirect()->route('grupos.index', $item)->with('exito', $mensaje);
    }
}
