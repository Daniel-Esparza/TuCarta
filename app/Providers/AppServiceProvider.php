<?php

namespace App\Providers;

use App\Contracts\ServicioImagen;
use App\Services\ImagenProducto;
use App\Support\NegocioActual;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(NegocioActual::class);
        $this->app->bind(ServicioImagen::class, ImagenProducto::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('seguimiento', function (Request $request) {
            return Limit::perMinute(20)
                ->by($request->ip())
                ->response(function () {
                    return response()->view('seguimiento.formulario', ['limitado' => true], 429);
                });
        });
    }
}
