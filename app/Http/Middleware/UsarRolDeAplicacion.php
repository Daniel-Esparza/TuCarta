<?php

namespace App\Http\Middleware;

use App\Support\NegocioActual;
use App\Support\SeguridadPorFilas;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Toda petición web trabaja con el rol tucarta_app para que PostgreSQL
 * aplique el RLS. Sin negocio fijado, las tablas con RLS no entregan filas.
 */
class UsarRolDeAplicacion
{
    public function handle(Request $request, Closure $next): Response
    {
        app(NegocioActual::class)->limpiar();
        SeguridadPorFilas::fijarNegocio(null);
        SeguridadPorFilas::usarRolDeAplicacion();

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        SeguridadPorFilas::restablecer();
        app(NegocioActual::class)->limpiar();
    }
}
