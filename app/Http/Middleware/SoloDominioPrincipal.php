<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * US-11a: la landing y el auto-registro solo se abren en el dominio principal.
 * En la dirección de un negocio responden 404; www lleva al dominio principal.
 */
class SoloDominioPrincipal
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        if (str_ends_with($host, '.'.config('tucarta.dominio'))) {
            if (str_starts_with($host, 'www.')) {
                return redirect()->away($request->getScheme().'://'.substr($request->getHttpHost(), 4).$request->getRequestUri(), 301);
            }

            abort(404);
        }

        return $next($request);
    }
}
