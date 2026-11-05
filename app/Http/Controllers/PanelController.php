<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Support\NegocioActual;
use Illuminate\View\View;

class PanelController extends Controller
{
    public function administrador(): View
    {
        return view('panel.administrador');
    }

    public function negocio(NegocioActual $negocioActual): View
    {
        // Sin filtro manual: el modelo ya solo trae las categorías del negocio actual.
        return view('panel.negocio', [
            'negocio' => $negocioActual->negocio(),
            'categorias' => Categoria::withCount('items')->orderBy('orden')->get(),
        ]);
    }

    public function categoria(Categoria $categoria): View
    {
        return view('panel.categoria', [
            'categoria' => $categoria->load('items'),
        ]);
    }
}
