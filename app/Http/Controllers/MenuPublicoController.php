<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Support\NegocioActual;
use Illuminate\View\View;

class MenuPublicoController extends Controller
{
    /**
     * US-12b: detalle de un producto del menú público.
     * El NegocioScope ya filtra por el negocio actual, así que si el item
     * no pertenece a este negocio el route model binding devuelve 404 solo.
     */
    public function detalle(Item $item, NegocioActual $negocioActual): View
    {
        abort_unless($item->activo, 404);

        $item->load('gruposOpciones.opciones');

        return view('menu.detalle', [
            'item'    => $item,
            'negocio' => $negocioActual->negocio(),
        ]);
    }

    /**
     * US-13: página del carrito del menú público.
     * Las líneas viven en el navegador; el servidor solo entrega el negocio
     * y el catálogo activo para que carrito-calculo.js pueda valuar los
     * precios en pantalla. El pedido se manda sin precios (acuerdo US-14a).
     */
    public function carrito(NegocioActual $negocioActual): View
    {
        $itemsMenu = Item::query()
            ->with('gruposOpciones.opciones')
            ->where('activo', true)
            ->get();

        return view('menu.carrito', [
            'negocio'   => $negocioActual->negocio(),
            'itemsMenu' => $itemsMenu,
        ]);
    }
}
