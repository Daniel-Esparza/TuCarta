<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Middleware\CompartirSesion;
use App\Http\Middleware\EnsurePermiso;
use App\Http\Middleware\ResolverNegocio;
use App\Http\Middleware\UsarRolDeAplicacion;
use App\Http\Middleware\VerificarNegocioPropio;
use App\Http\Middleware\VerificarRol;
use App\Support\Dominio;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Las peticiones llegan por Traefik
        $middleware->trustProxies(at: '*');

        $middleware->web(prepend: [CompartirSesion::class], append: [UsarRolDeAplicacion::class]);

        $middleware->alias([
            'negocio' => ResolverNegocio::class,
            'negocio.propio' => VerificarNegocioPropio::class,
            'rol' => VerificarRol::class,
            // US-23: proteger rutas por permiso
            'permiso' => EnsurePermiso::class,
        ]);

        // El negocio se fija antes de buscar registros por la URL; si no, el
        // route model binding podría encontrar un registro de otro negocio.
        $middleware->prependToPriorityList(AuthenticatesRequests::class, UsarRolDeAplicacion::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, ResolverNegocio::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, VerificarNegocioPropio::class);
        $middleware->prependToPriorityList(SubstituteBindings::class, VerificarRol::class);

        // Sin sesión se va al login unificado (auth.{dominio})
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn (Request $request) => LoginController::inicioDe($request->user()));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
