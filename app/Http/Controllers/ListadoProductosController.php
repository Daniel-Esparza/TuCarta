<?php

namespace App\Http\Controllers;

use App\Contracts\ServicioImagen;
use App\Models\Categoria;
use App\Models\Item;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ListadoProductosController extends Controller
{
    public function __construct(private ServicioImagen $imagenes) {}

    public function index(Request $request): View
    {
        $negocioId = $this->negocioDelDueno($request);

        $productos = $this->filtrados($request, $negocioId)
            ->with('categoria')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('productos.index', [
            'productos' => $productos,
            'categorias' => Categoria::where('negocio_id', $negocioId)->orderBy('orden')->orderBy('id')->get(),
            'destino' => $request->integer('destino'),
        ]);
    }

    public function mover(Request $request): RedirectResponse
    {
        $negocioId = $this->negocioDelDueno($request);

        $datos = $request->validate([
            'categoria_id' => ['required', 'integer', Rule::exists('categorias', 'id')->where('negocio_id', $negocioId)],
            'productos' => ['nullable', 'array'],
            'productos.*' => ['integer'],
        ], [
            'categoria_id.required' => 'Elige la categoría a la que quieres moverlos.',
            'categoria_id.exists' => 'La categoría no es de tu negocio.',
        ]);

        $seleccion = $request->boolean('todos')
            ? $this->filtrados($request, $negocioId)
            : Item::where('negocio_id', $negocioId)->whereIn('id', $datos['productos'] ?? []);

        $movidos = $seleccion->update(['categoria_id' => $datos['categoria_id']]);

        if ($movidos === 0) {
            throw ValidationException::withMessages(['productos' => 'Selecciona al menos un producto.']);
        }

        return back()->with('exito', "Se movieron {$movidos} producto(s).");
    }

    public function destroy(Request $request, Item $producto): RedirectResponse
    {
        abort_unless((int) $producto->negocio_id === $this->negocioDelDueno($request), 404, 'Producto no encontrado.');

        // pedido_items guarda el nombre y el precio del día; su enlace al producto queda vacío.
        $producto->delete();

        $this->imagenes->eliminar($producto->imagen, $producto->miniatura);

        return redirect()->route('productos.index')->with('exito', "Se eliminó {$producto->nombre}.");
    }

    private function filtrados(Request $request, int $negocioId): Builder
    {
        $disponibilidad = $request->input('disponibilidad');

        return Item::where('negocio_id', $negocioId)
            ->when($request->filled('q'), fn ($consulta) => $consulta
                ->whereRaw('lower(nombre) like ?', ['%'.mb_strtolower($request->string('q')).'%']))
            ->when($request->integer('categoria'), fn ($consulta, $id) => $consulta->where('categoria_id', $id))
            ->when(in_array($disponibilidad, ['disponible', 'agotado'], true), fn ($consulta) => $consulta
                ->where('disponible_hoy', $disponibilidad === 'disponible'));
    }

    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;
        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return (int) $negocioId;
    }
}
