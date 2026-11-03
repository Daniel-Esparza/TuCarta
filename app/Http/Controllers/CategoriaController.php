<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriaController extends Controller
{
    public function index(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $categorias = Categoria::where('negocio_id', $negocioId)
            ->with('items')
            ->orderBy('orden')->orderBy('id')
            ->get();

        return view('categorias.index', ['categorias' => $categorias]);
    }

    public function store(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:80', $this->reglaNombreUnico($negocioId)],
        ], $this->mensajes());

        Categoria::create([
            'negocio_id' => $negocioId,
            'nombre' => $datos['nombre'],
            'orden' => (Categoria::where('negocio_id', $negocioId)->max('orden') ?? -1) + 1,
        ]);

        return redirect()->route('categorias.index')->with('exito', 'Categoría creada.');
    }

    public function update(Request $request, Categoria $categoria)
    {
        $negocioId = $this->negocioDelDueno($request);
        $this->abortSiNoEsMia($categoria, $negocioId);

        $datos = $request->validateWithBag('editar', [
            'nombre' => ['required', 'string', 'max:80', $this->reglaNombreUnico($negocioId, $categoria->id)],
        ], $this->mensajes());

        $categoria->update(['nombre' => $datos['nombre']]);

        return redirect()->route('categorias.index')->with('exito', 'Categoría actualizada.');
    }

    public function destroy(Request $request, Categoria $categoria)
    {
        $negocioId = $this->negocioDelDueno($request);
        $this->abortSiNoEsMia($categoria, $negocioId);

        $total = $categoria->items()->count();
        if ($total > 0) {
            return redirect()->route('categorias.index')->with(
                'error',
                "No se puede eliminar: tiene {$total} producto(s). Muévelos o elimínalos primero."
            );
        }

        $categoria->delete();

        return redirect()->route('categorias.index')->with('exito', 'Categoría eliminada.');
    }

    public function subir(Request $request, Categoria $categoria)
    {
        return $this->mover($request, $categoria, -1);
    }

    public function bajar(Request $request, Categoria $categoria)
    {
        return $this->mover($request, $categoria, 1);
    }

    private function mover(Request $request, Categoria $categoria, int $delta)
    {
        $negocioId = $this->negocioDelDueno($request);
        $this->abortSiNoEsMia($categoria, $negocioId);

        DB::transaction(function () use ($negocioId, $categoria, $delta) {
            $ids = Categoria::where('negocio_id', $negocioId)
                ->orderBy('orden')->orderBy('id')->pluck('id')->all();

            $pos = array_search($categoria->id, $ids);
            $nueva = $pos + $delta;
            if ($nueva < 0 || $nueva >= count($ids)) {
                return;
            }

            [$ids[$pos], $ids[$nueva]] = [$ids[$nueva], $ids[$pos]];

            foreach ($ids as $i => $id) {
                Categoria::where('id', $id)->update(['orden' => $i]);
            }
        });

        return redirect()->route('categorias.index');
    }

    private function reglaNombreUnico(int $negocioId, ?int $ignorarId = null): \Closure
    {
        return function ($atributo, $valor, $fallar) use ($negocioId, $ignorarId) {
            $existe = Categoria::where('negocio_id', $negocioId)
                ->whereRaw('lower(nombre) = ?', [mb_strtolower($valor)])
                ->when($ignorarId, fn ($q) => $q->where('id', '!=', $ignorarId))
                ->exists();

            if ($existe) {
                $fallar('Ya tienes una categoría con ese nombre.');
            }
        };
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre de la categoría.',
            'nombre.max' => 'El nombre no puede pasar de 80 caracteres.',
        ];
    }

    private function abortSiNoEsMia(Categoria $categoria, int $negocioId): void
    {
        abort_unless((int) $categoria->negocio_id === $negocioId, 404, 'Categoría no encontrada.');
    }

    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;
        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return (int) $negocioId;
    }
}