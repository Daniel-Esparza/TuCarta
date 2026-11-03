<?php

namespace App\Http\Middleware;

use App\Enums\ClavePermiso;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas: ->middleware('permiso:avanzar_pedidos'). La clave debe
 * existir en ClavePermiso; si no existe, la ruta falla desde el inicio.
 */
class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string $clave): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario instanceof Usuario && $usuario->tienePermiso(ClavePermiso::from($clave)),
            403,
            'No tienes permiso para entrar a esta sección.'
        );

        return $next($request);
    }
}
