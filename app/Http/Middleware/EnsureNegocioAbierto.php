<?php

namespace App\Http\Middleware;

use App\Support\NegocioActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * US-19b: el negocio de la dirección debe estar abierto para recibir pedidos.
 *
 * Usa Negocio::estaAbierto() de US-19a: el cierre temporal manda sobre el horario.
 * Va dentro del grupo del subdominio: el middleware "negocio" ya descartó los negocios
 * que no están activos. El bloqueo se hace aquí, en el servidor; esconder el botón no basta.
 *
 * Todavía no existe el envío real de pedidos. Este middleware se aplica hoy a una ruta de
 * prueba y debe ponerse en la ruta que envíe el pedido cuando exista.
 */
class EnsureNegocioAbierto
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            app(NegocioActual::class)->negocio()->estaAbierto(),
            403,
            'El negocio está cerrado y no recibe pedidos en este momento.'
        );

        return $next($request);
    }
}
