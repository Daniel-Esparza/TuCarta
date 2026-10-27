<?php

namespace App\Http\Middleware;

use App\Models\Negocio;
use App\Support\NegocioActual;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Busca el negocio del subdominio y lo fija para toda la petición.
 *
 * US-02b: pendiente, rechazado, reservado o inexistente responden la misma
 * página 404, para no revelar cuál es cuál. Suspendido muestra su aviso.
 */
class ResolverNegocio
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdominio = (string) $request->route('subdominio');
        $request->route()->forgetParameter('subdominio');

        if (in_array($subdominio, config('tucarta.reservados'), true)) {
            abort(404);
        }

        $negocio = Negocio::where('subdominio', $subdominio)->first();

        if ($negocio?->estado === 'suspendido') {
            return response()->view('negocio.suspendido', ['negocio' => $negocio]);
        }

        if ($negocio?->estado !== 'activo') {
            abort(404);
        }

        app(NegocioActual::class)->establecer($negocio);
        URL::defaults(['subdominio' => $negocio->subdominio]);
        View::share('negocio', $negocio);

        return $next($request);
    }
}
