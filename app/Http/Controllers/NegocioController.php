<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NegocioController extends Controller
{

    public function index(): View
    {
        Gate::authorize('viewAny', Negocio::class);

        $usuario = auth()->user();

        if ($usuario->tienePermisoGlobal('gestionar_negocios')) {
            $negocios = Negocio::orderBy('nombre')->get();
        } else {
            $negocios = Negocio::whereIn('id', $usuario->negociosConPermiso('gestionar_negocios'))
                ->orderBy('nombre')
                ->get();
        }

        return view('negocios.index', ['negocios' => $negocios]);
    }
}