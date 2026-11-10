<?php

namespace App\Providers;

use App\Contracts\ServicioImagen;
use App\Services\ImagenProducto;
use App\Support\NegocioActual;
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
        //
    }
}
