<?php

namespace App\Http\Middleware;

use App\Support\NegocioActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El panel de un negocio solo lo abren su dueño y sus empleados. A cualquier
 * otro se le responde 404, para no confirmar que existe.
 */
class VerificarNegocioPropio
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if ($usuario === null || ! $usuario->esNegocio() || $usuario->negocio_id !== app(NegocioActual::class)->id()) {
            abort(404);
        }

        return $next($request);
    }
}
