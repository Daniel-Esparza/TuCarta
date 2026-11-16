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
        $item->load('gruposOpciones.opciones');

        return view('menu.detalle', [
            'item'    => $item,
            'negocio' => $negocioActual->negocio(),
        ]);
    }
}
