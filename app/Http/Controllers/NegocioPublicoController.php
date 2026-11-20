<?php

namespace App\Http\Controllers;

use App\Support\NegocioActual;
use Illuminate\View\View;

class NegocioPublicoController extends Controller
{
    /**
     * US-12a + US-13: la dirección pública de un negocio activo muestra su carta
     * con los datos que el carrito del navegador necesita.
     * Los demás estados los resuelve el middleware "negocio" antes de llegar aquí.
     */
    public function mostrar(NegocioActual $negocioActual): View
    {
        $negocio = $negocioActual->negocio();

        $categorias = $negocio->categorias()
            ->orderBy('orden')->orderBy('id')
            ->whereHas('items', fn ($q) => $q->where('activo', true))
            ->with(['items' => fn ($q) => $q->where('activo', true)->with('gruposOpciones.opciones')])
            ->get();

        return view('negocio.menu', [
            'negocio'    => $negocio,
            'categorias' => $categorias,
            'horarios'   => $negocio->horarios()->get()->groupBy('dia_semana')->sortKeys(),
            'itemsMenu'  => $categorias->flatMap(fn ($c) => $c->items),
        ]);
    }
}
