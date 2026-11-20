<?php

namespace App\Http\Middleware;

use App\Enums\EstadoNegocio;
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
 * US-11a: el negocio sale siempre de la dirección, nunca de un dato de la
 * petición. Controladores y vistas lo leen de NegocioActual o de $negocio.
 *
 * US-02b: pendiente, rechazado, reservado o inexistente responden la misma
 * página 404, para no revelar cuál es cuál. Suspendido muestra su aviso.
 */
class ResolverNegocio
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdominio = mb_strtolower((string) $request->route('subdominio'));
        $request->route()->forgetParameter('subdominio');

        if ($subdominio === 'www') {
            return redirect()->away($request->getScheme().'://'.substr($request->getHttpHost(), 4).$request->getRequestUri(), 301);
        }

        if (in_array($subdominio, config('tucarta.reservados'), true)) {
            abort(404);
        }

        $negocio = Negocio::where('subdominio', $subdominio)
            ->where('estado', '<>', EstadoNegocio::Rechazado)
            ->first();

        if ($negocio?->estado === EstadoNegocio::Suspendido) {
            return response()->view('negocio.suspendido', ['negocio' => $negocio]);
        }

        if ($negocio?->estado !== EstadoNegocio::Activo) {
            abort(404);
        }

        app(NegocioActual::class)->establecer($negocio);
        URL::defaults(['subdominio' => $negocio->subdominio]);
        View::share('negocio', $negocio);

        return $next($request);
    }
}
