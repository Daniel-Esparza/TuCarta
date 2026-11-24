<?php

namespace App\Http\Controllers;

use App\Enums\TipoEntrega;
use App\Models\Pedido;
use App\Services\PedidoService;
use App\Services\WhatsappPedidoService;
use App\Support\NegocioActual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PedidoController extends Controller
{
    public function confirmar(): View
    {
        return view('pedido.confirmar');
    }

    public function store(Request $request, PedidoService $pedidos): RedirectResponse
    {
        $datos = $request->validate([
            'cliente_nombre' => ['required', 'string', 'max:120'],
            'notas' => ['nullable', 'string', 'max:500'],
            'carrito' => ['required', 'json'],
            'tipo_entrega' => ['required', Rule::enum(TipoEntrega::class)],
            'direccion_entrega' => ['nullable', 'required_if:tipo_entrega,domicilio', 'string', 'max:255'],
            'referencias_entrega' => ['nullable', 'string', 'max:255'],
        ], [
            'tipo_entrega.required' => 'Elige cómo quieres tu pedido.',
            'tipo_entrega.enum' => 'El tipo de pedido no es válido.',
            'direccion_entrega.required_if' => 'Escribe la dirección para el envío a domicilio.',
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
            tipo: TipoEntrega::from($datos['tipo_entrega']),
            direccion: $datos['direccion_entrega'] ?? null,
            referencias: $datos['referencias_entrega'] ?? null,
        );

        return redirect()->route('pedido.gracias')->with([
            'folio' => $pedido->folio,
            'pedido_id' => $pedido->id,
        ]);
    }

    public function gracias(NegocioActual $negocioActual, WhatsappPedidoService $whatsapp): View|RedirectResponse
    {
        if (! session()->has('folio')) {
            return redirect()->route('negocio.publico');
        }

        $negocio = $negocioActual->negocio();
        $pedido = null;

        if (session()->has('pedido_id')) {
            $pedido = Pedido::where('id', session('pedido_id'))
                ->where('negocio_id', $negocio->id)
                ->firstOrFail();
        } else {
            $pedido = Pedido::where('folio', session('folio'))
                ->where('negocio_id', $negocio->id)
                ->firstOrFail();
        }

        return view('pedido.gracias', [
            'folio' => $pedido->folio,
            'pedido' => $pedido,
            'negocio' => $negocio,
            'whatsappUrl' => $whatsapp->generarUrl($pedido, $negocio),
        ]);
    }
}
