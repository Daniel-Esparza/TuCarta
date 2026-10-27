<?php

namespace App\Http\Controllers;

use App\Support\NegocioActual;
use Illuminate\View\View;

class NegocioPublicoController extends Controller
{
    /**
     * US-02b: la dirección de un negocio activo muestra su página provisional.
     * Los demás estados los resuelve el middleware "negocio" antes de llegar aquí.
     */
    public function mostrar(NegocioActual $negocioActual): View
    {
        return view('negocio.provisional', ['negocio' => $negocioActual->negocio()]);
    }
}
