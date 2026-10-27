<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decisión D4: la sesión que se abre en auth.{dominio} vale en el dominio
 * principal y en todos los subdominios. Si se entra por otra dirección
 * (por ejemplo http://localhost) la sesión queda solo en esa dirección.
 */
class CompartirSesion
{
    public function handle(Request $request, Closure $next): Response
    {
        $dominio = config('tucarta.dominio');

        if (config('session.domain') === null && str_ends_with($request->getHost(), $dominio)) {
            config(['session.domain' => '.'.$dominio]);
        }

        return $next($request);
    }
}
