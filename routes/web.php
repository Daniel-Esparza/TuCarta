<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AutoRegistroNegocioController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ModeradorController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\NegocioPublicoController;
use App\Http\Controllers\PanelController;
use Illuminate\Support\Facades\Route;

$dominio = config('tucarta.dominio');

// Login unificado y panel central (auth.{dominio}). No es un negocio.
Route::domain('auth.'.$dominio)->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/', [LoginController::class, 'create'])->name('login');
        Route::post('/', [LoginController::class, 'store'])->name('login.store');
    });
    Route::post('/salir', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

    Route::middleware(['auth', 'rol:administrador'])->prefix('panel')->group(function () {
        Route::get('/', [PanelController::class, 'administrador'])->name('panel.administrador');

        // US-27: moderadores y permisos
        Route::prefix('moderadores')->name('moderadores.')->group(function () {
            Route::get('/', [ModeradorController::class, 'index'])->name('index');
            Route::post('/', [ModeradorController::class, 'store'])->name('store');
            Route::post('{moderador}/permisos', [ModeradorController::class, 'asignarPermiso'])->name('permisos.store');
            Route::delete('{moderador}/permisos/{asignacion}', [ModeradorController::class, 'quitarPermiso'])->name('permisos.destroy');
        });

        // US-03: lista y estado de negocios
        Route::prefix('negocios')->name('negocios.')->group(function () {
            Route::get('/', [NegocioController::class, 'index'])->name('index');
            Route::patch('{negocio}/suspender', [NegocioController::class, 'suspender'])->name('suspender');
            Route::patch('{negocio}/reactivar', [NegocioController::class, 'reactivar'])->name('reactivar');
        });
    });
});

// Todo lo de un negocio vive en su subdominio.
Route::domain('{subdominio}.'.$dominio)->middleware('negocio')->group(function () {
    // US-02b: página provisional hasta que exista el menú (US-11)
    Route::get('/', [NegocioPublicoController::class, 'mostrar'])->name('negocio.publico');

    Route::middleware(['auth', 'negocio.propio'])->prefix('panel')->group(function () {
        Route::get('/', [PanelController::class, 'negocio'])->name('panel.negocio');
        Route::get('/categorias/{categoria}', [PanelController::class, 'categoria'])->name('panel.negocio.categoria');

        // US-23: equipo de trabajo del dueño
        Route::middleware('permiso:gestionar_equipo')->group(function () {
            Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
            Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
            Route::patch('/equipo/{empleado}/desactivar', [EquipoController::class, 'desactivar'])->name('equipo.desactivar');
        });

        // US-23: rutas de prueba de cada permiso, hasta que existan sus pantallas
        Route::get('/prueba/avanzar-pedidos', fn () => 'Permiso avanzar_pedidos: OK')
            ->middleware('permiso:avanzar_pedidos')->name('prueba.avanzar-pedidos');
        Route::get('/prueba/marcar-agotado', fn () => 'Permiso marcar_agotado: OK')
            ->middleware('permiso:marcar_agotado')->name('prueba.marcar-agotado');
        Route::get('/prueba/ver-metricas', fn () => 'Permiso ver_metricas: OK')
            ->middleware('permiso:ver_metricas')->name('prueba.ver-metricas');
        Route::get('/prueba/editar-precios', fn () => 'Permiso editar_precios: OK')
            ->middleware('permiso:editar_precios')->name('prueba.editar-precios');
        Route::get('/prueba/editar-perfil', fn () => 'Permiso editar_perfil: OK')
            ->middleware('permiso:editar_perfil')->name('prueba.editar-perfil');
    });
});

// Dominio principal
Route::view('/', 'landing')->name('landing');

// US-26a: auto-registro público de negocios (sin sesión)
Route::get('/registrar-negocio', [AutoRegistroNegocioController::class, 'create'])->name('auto-registro.create');
Route::post('/registrar-negocio', [AutoRegistroNegocioController::class, 'store'])->name('auto-registro.store');
