<?php

namespace App\Http\Controllers;

use App\Models\Pedido;
use App\Support\NegocioActual;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SeguimientoPedidoController extends Controller
{
    public function formulario(): View
    {
        return view('seguimiento.formulario');
    }

    public function buscar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'folio' => ['required', 'string', 'max:20'],
        ]);

        // Mismo formato que se guarda: sin espacios y en mayúsculas.
        $folio = strtoupper(trim($datos['folio']));

        return redirect()->route('seguimiento.mostrar', [
            'folio' => $folio,
        ]);
    }

    public function mostrar(Request $request, string $folio)
    {
        $folio = strtoupper(trim($folio));
        $negocio = app(NegocioActual::class)->negocio();

        if (! $negocio) {
            abort(404);
        }

        $pedido = Pedido::where('negocio_id', $negocio->id)
            ->where('folio', $folio)
            ->first();

        if (! $pedido) {
            return response()->view('seguimiento.formulario', [
                'noEncontrado' => true,
                'folio' => $folio,
            ], 404);
        }

        $pasos = $pedido->historial()
            ->orderBy('created_at')
            ->get(['estado_nuevo', 'created_at']);

        return view('seguimiento.mostrar', [
            'pedido' => $pedido,
            'pasos' => $pasos,
        ]);
    }
}
