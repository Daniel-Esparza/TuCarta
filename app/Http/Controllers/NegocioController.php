<?php

namespace App\Http\Controllers;

use App\Models\Negocio;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class NegocioController extends Controller
{
    /**
     * US-03: lista de negocios con busqueda, filtro por estado y paginacion.
     * El moderador sin permisos ve la lista vacia; dueño y empleado no entran.
     */
    public function index(Request $request): View
    {
        $usuario = $request->user();
        abort_unless($usuario->esAdministrador(), 403);

        $negocios = Negocio::query()
            ->when(! $usuario->tienePermisoGlobal('gestionar_negocios'), fn ($q) => $q
                ->whereIn('id', $usuario->negociosConPermiso('gestionar_negocios')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'ilike', '%'.$request->string('q').'%')
                ->orWhere('subdominio', 'ilike', '%'.$request->string('q').'%')))
            ->when(in_array($request->estado, ['activo', 'pendiente', 'suspendido', 'rechazado'], true),
                fn ($q) => $q->where('estado', $request->estado))
            ->withCount('items')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('negocios.index', ['negocios' => $negocios]);
    }

  
    public function suspender(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('update', $negocio);

        $negocio->update(['estado' => 'suspendido']);

        return redirect()->route('negocios.index')
            ->with('estado', "{$negocio->nombre} suspendido.");
    }

    public function reactivar(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('update', $negocio);

        $negocio->update(['estado' => 'activo']);

        return redirect()->route('negocios.index')
            ->with('estado', "{$negocio->nombre} reactivado.");
    }
}