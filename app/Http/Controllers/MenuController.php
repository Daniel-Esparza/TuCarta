<?php

namespace App\Http\Controllers;

use App\Models\Negocio;

class MenuController extends Controller
{

    public function inicio()
    {
        $negocios = Negocio::all();

        return view('negocios.index', compact('negocios'));
    }


    public function menu(int $id)
    {
        $negocio = Negocio::with('categorias.item')->findOrFail($id);

        return view('negocios.menu', compact('negocio'));
    }
}