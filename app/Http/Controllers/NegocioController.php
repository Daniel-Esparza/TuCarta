<?php

namespace App\Http\Controllers;

use App\Enums\ClavePermiso;
use App\Enums\EstadoNegocio;
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
            ->when(! $usuario->tienePermisoGlobal(ClavePermiso::GestionarNegocios), fn ($q) => $q
                ->whereIn('id', $usuario->negociosConPermiso(ClavePermiso::GestionarNegocios)))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('nombre', 'ilike', '%'.$request->string('q').'%')
                ->orWhere('subdominio', 'ilike', '%'.$request->string('q').'%')))
            ->when(EstadoNegocio::tryFrom((string) $request->estado),
                fn ($q, $estado) => $q->where('estado', $estado))
            ->withCount('items')
            ->orderBy('nombre')
            ->paginate(20)
            ->withQueryString();

        return view('negocios.index', ['negocios' => $negocios]);
    }

  
    public function suspender(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('update', $negocio);

        $negocio->update(['estado' => EstadoNegocio::Suspendido]);

        return redirect()->route('negocios.index')
            ->with('estado', "{$negocio->nombre} suspendido.");
    }

    public function reactivar(Request $request, Negocio $negocio): RedirectResponse
    {
        Gate::authorize('update', $negocio);

        $negocio->update(['estado' => EstadoNegocio::Activo]);

        return redirect()->route('negocios.index')
            ->with('estado', "{$negocio->nombre} reactivado.");
    }
}