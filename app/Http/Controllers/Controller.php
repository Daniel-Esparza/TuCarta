<?php

namespace App\Http\Controllers;

use App\Models\Restaurante;

class MenuController extends Controller
{
    
    public function inicio()
    {
        $restaurantes = Restaurante::all();

        return view('restaurantes.index', compact('restaurantes'));
    }


    public function menu(int $id)
    {
        $restaurante = Restaurante::with('categorias.platillos')->findOrFail($id);

        return view('restaurantes.menu', compact('restaurante'));
    }
}