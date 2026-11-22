<?php

namespace App\Http\Controllers;

use App\Services\PedidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function confirmar(): View
    {
        return view('pedido.confirmar');
    }

    public function store(Request $request, PedidoService $pedidos): RedirectResponse
    {
        $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
            'carrito' => ['required', 'json'],
        ], [
            'cliente_nombre.required' => 'Escribe tu nombre para confirmar el pedido.',
            'carrito.required' => 'Tu carrito está vacío.',
            'carrito.json' => 'No se pudo leer tu carrito. Vuelve a agregar los productos.',
        ]);

        $lineas = json_decode($request->input('carrito'), true);

        Validator::make(['lineas' => $lineas], [
            'lineas' => ['required', 'array', 'min:1', 'max:50'],
            'lineas.*.item_id' => ['required', 'integer'],
            'lineas.*.cantidad' => ['required', 'integer', 'between:1,99'],
            'lineas.*.opciones' => ['nullable', 'array'],
            'lineas.*.opciones.*' => ['integer'],
        ], [
            'lineas.required' => 'Tu carrito está vacío.',
            'lineas.*.item_id.required' => 'Revisa los productos de tu carrito.',
            'lineas.*.cantidad.between' => 'La cantidad debe estar entre 1 y 99.',
        ])->validate();

        $pedido = $pedidos->crear(
            clienteNombre: $request->input('cliente_nombre'),
            notas: $request->input('notas'),
            lineas: $lineas,
            ip: $request->ip(),
        );

        return redirect()->route('pedido.gracias')->with('folio', $pedido->folio);
    }

    public function gracias(): View|RedirectResponse
    {
        if (! session()->has('folio')) {
            return redirect()->route('negocio.publico');
        }

        return view('pedido.gracias', ['folio' => session('folio')]);
    }
}
