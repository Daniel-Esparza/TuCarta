<?php

namespace App\Http\Controllers;

use App\Contracts\ServicioImagen;
use App\Models\Categoria;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class ProductoController extends Controller
{
    public function __construct(private ServicioImagen $imagenes) {}

    public function create(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);

        $categorias = Categoria::where('negocio_id', $negocioId)
            ->orderBy('orden')->orderBy('id')
            ->get();

        return view('productos.create', [
            'categorias' => $categorias,
            'categoriaInicial' => $request->query('categoria'),
        ]);
    }

    public function store(Request $request)
    {
        $negocioId = $this->negocioDelDueno($request);
        $datos = $request->validate($this->reglas($negocioId), $this->mensajes());

        $producto = Item::create([
            'negocio_id' => $negocioId,
            'categoria_id' => $datos['categoria_id'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'precio' => $datos['precio'],
            'disponible_hoy' => $request->boolean('disponible_hoy'),
        ] + $this->imagenSubida($request, $negocioId));

        return redirect()
            ->route('panel.negocio.categoria', $producto->categoria_id)
            ->with('exito', 'Producto creado.');
    }

    public function edit(Request $request, Item $producto)
    {
        $negocioId = $this->negocioDelDueno($request);
        $this->abortSiNoEsMio($producto, $negocioId);

        $categorias = Categoria::where('negocio_id', $negocioId)
            ->orderBy('orden')->orderBy('id')
            ->get();

        return view('productos.edit', ['producto' => $producto, 'categorias' => $categorias]);
    }

    public function update(Request $request, Item $producto)
    {
        $negocioId = $this->negocioDelDueno($request);
        $this->abortSiNoEsMio($producto, $negocioId);

        $datos = $request->validate($this->reglas($negocioId), $this->mensajes());

        $nuevas = $this->imagenSubida($request, $negocioId);
        $imagenAnterior = $producto->imagen;
        $miniaturaAnterior = $producto->miniatura;

        $producto->update([
            'categoria_id' => $datos['categoria_id'],
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'precio' => $datos['precio'],
            'disponible_hoy' => $request->boolean('disponible_hoy'),
        ] + $nuevas);

        if ($nuevas !== []) {
            $this->imagenes->eliminar($imagenAnterior, $miniaturaAnterior);
        }

        return redirect()
            ->route('panel.negocio.categoria', $producto->categoria_id)
            ->with('exito', 'Producto actualizado.');
    }

    private function reglas(int $negocioId): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'descripcion' => ['nullable', 'string'],
            'precio' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'categoria_id' => [
                'required', 'integer',
                Rule::exists('categorias', 'id')->where('negocio_id', $negocioId),
            ],
            'imagen' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function mensajes(): array
    {
        return [
            'nombre.required' => 'Escribe el nombre del producto.',
            'nombre.max' => 'El nombre no puede pasar de 120 caracteres.',
            'precio.required' => 'Escribe el precio.',
            'precio.numeric' => 'El precio debe ser un número, por ejemplo 25.50.',
            'precio.gt' => 'El precio debe ser mayor a cero.',
            'precio.max' => 'El precio es demasiado grande.',
            'categoria_id.required' => 'Elige una categoría.',
            'categoria_id.exists' => 'La categoría no es de tu negocio.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.mimes' => 'La imagen debe ser JPG, PNG o WebP.',
            'imagen.max' => 'La imagen no puede pesar más de 5 MB.',
        ];
    }

    private function imagenSubida(Request $request, int $negocioId): array
    {
        if (! $request->hasFile('imagen')) {
            return [];
        }

        try {
            return $this->imagenes->guardar($request->file('imagen'), $negocioId);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['imagen' => 'No se pudo leer la imagen. Prueba con otro archivo.']);
        }
    }

    private function negocioDelDueno(Request $request): int
    {
        $negocioId = $request->user()?->negocio_id;
        abort_if($negocioId === null, 403, 'Esta pantalla es solo para cuentas de negocio.');

        return (int) $negocioId;
    }

    private function abortSiNoEsMio(Item $producto, int $negocioId): void
    {
        abort_unless((int) $producto->negocio_id === $negocioId, 404, 'Producto no encontrado.');
    }
}
