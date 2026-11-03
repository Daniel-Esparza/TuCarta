<?php

namespace App\Http\Middleware;

use App\Enums\Rol;
use App\Support\NegocioActual;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uso en rutas: ->middleware('rol:administrador'). Si el rol no coincide
 * responde 404.
 */
class VerificarRol
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $usuario = $request->user();

        if ($usuario === null || ! in_array($usuario->rol->value, $roles, true)) {
            abort(404);
        }

        if ($usuario->rol === Rol::Administrador) {
            app(NegocioActual::class)->verTodo();
        }

        return $next($request);
    }
}
