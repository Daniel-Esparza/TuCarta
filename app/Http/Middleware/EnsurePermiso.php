<?php
namespace App\Http\Middleware;

use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePermiso
{
    public function handle(Request $request, Closure $next, string $clave): Response
    {
        $usuario = $request->user();

        abort_unless(
            $usuario instanceof Usuario && $usuario->tienePermiso($clave),
            403,
            'No tienes permiso para entrar a esta sección.'
        );

        return $next($request);
    }
}
