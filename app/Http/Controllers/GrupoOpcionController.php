<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\GrupoOpcion;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GrupoOpcionController extends Controller
{
    private const MAX_OPCIONES = 20;

    public function index(Request $request, Item $item)
    {
        $negocioId = $this->negocioDelDueno($request);
        abort_unless((int) $item->negocio_id === $negocioId, 404, 'Producto no encontrado.');

        $grupos = GrupoOpcion::where('item_id', $item->id)
            ->with('opciones')
            ->orderBy('orden')->orderBy('id')
            ->get();

        $categorias = Categoria::where('negocio_id', $negocioId)
            ->with(['items' => fn ($consulta) => $consulta->whereKeyNot($item->id)->orderBy('nombre')])
            ->orderBy('orden')->orderBy('id')
            ->get()
            ->filter(fn ($categoria) => $categoria->items->isNotEmpty());

        return view('productos.grupos', ['producto' => $item, 'grupos' => $grupos, 'categorias' => $categorias]);
    }

    public function store(Request $request, Item $item)
    {
        $negocioId = $this->negocioDelDueno($request);
        abort_unless((int) $item->negocio_id === $negocioId, 404, 'Producto no encontrado.');

        $datos = $this->validados($request);

        GrupoOpcion::create($datos + [
            'item_id' => $item->id,
            'negocio_id' => $negocioId,
            'orden' => (GrupoOpcion::where('item_id', $item->id)->max('orden') ?? -1) + 1,
        ]);

        return redirect()->route('grupos.index', $item)->with('exito', 'Grupo creado.');
    }

    public function update(Request $request, Item $item, GrupoOpcion $grupo)
    {
        $this->abortSiNoEsDelProducto($request, $item, $grupo);

        $grupo->update($this->validados($request));

        return redirect()->route('grupos.index', $item)->with('exito', 'Grupo actualizado.');
    }

    public function destroy(Request $request, Item $item, GrupoOpcion $grupo)
    {
        $this->abortSiNoEsDelProducto($request, $item, $grupo);

        // Sus opciones se borran en cascada; el producto y los pedidos anteriores no se tocan.
        $grupo->delete();

        return redirect()->route('grupos.index', $item)
            ->with('exito', 'Grupo eliminado. Sus opciones también se eliminaron.');
    }

    private function validados(Request $request): array
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'min_selecciones' => ['required', 'integer', 'min:0', 'max:'.self::MAX_OPCIONES],
            'max_selecciones' => ['required', 'integer', 'min:1', 'max:'.self::MAX_OPCIONES],
        ], [
            'nombre.required' => 'Escribe el nombre del grupo.',
            'nombre.max' => 'El nombre no puede pasar de 100 caracteres.',
        ]);

        $multiple = $request->boolean('es_multiple');
        $obligatorio = $request->boolean('es_obligatorio');

        // Selección única: solo se puede elegir una opción.
        $max = $multiple ? (int) $datos['max_selecciones'] : 1;
        $min = $multiple ? (int) $datos['min_selecciones'] : min((int) $datos['min_selecciones'], 1);

        // Obligatorio: al menos una opción.
        if ($obligatorio) {
            $min = max($min, 1);
        }

        if ($min > $max) {
            throw ValidationException::withMessages([
                'min_selecciones' => 'El mínimo no puede ser mayor que el máximo.',
            ]);
        }

        return [
            'nombre' => $datos['nombre'],
            'es_multiple' => $multiple,
            'es_obligatorio' => $obligatorio,
            'min_selecciones' => $min,
            'max_selecciones' => $max,
        ];
    }

    private function abortSiNoEsDelProducto(Request $request, Item $item, GrupoOpcion $grupo): void
    {
        $negocioId = $this->negocioDelDueno($request);

        abort_unless((int) $item->negocio_id === $negocioId, 404, 'Producto no encontrado.');
        abort_unless(
            (int) $grupo->negocio_id === $negocioId && (int) $grupo->item_id === (int) $item->id,
            404,
            'Grupo no encontrado.'
        );
    }

    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;
        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return (int) $negocioId;
    }
}
