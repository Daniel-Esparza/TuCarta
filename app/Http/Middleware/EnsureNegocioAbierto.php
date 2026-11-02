<?php

namespace App\Http\Middleware;

use App\Models\Negocio;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * US-19b: el negocio de la direccion ({subdominio}.{dominio}) debe estar abierto.
 *
 * Usa Negocio::estaAbierto() de US-19a: el cierre temporal manda sobre el horario.
 * El bloqueo se hace aqui, en el servidor; esconder el boton no basta.
 *
 * Todavia no existe el envio real de pedidos. Este middleware se aplica hoy a una ruta de
 * prueba y debe ponerse en la ruta que envie el pedido cuando exista.
 */
class EnsureNegocioAbierto
{
    public function handle(Request $request, Closure $next): Response
    {
        $subdominio = $request->route('subdominio');

        $negocio = $subdominio ? Negocio::where('subdominio', $subdominio)->first() : null;

        // Igual que la pagina del negocio: si no esta activo, no se confirma que existe.
        abort_unless($negocio?->estado === 'activo', 404);

        abort_unless(
            $negocio->estaAbierto(),
            403,
            'El negocio está cerrado y no recibe pedidos en este momento.'
        );

        return $next($request);
    }
}
