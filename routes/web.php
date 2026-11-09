<?php

use App\Http\Controllers\AltaNegocioController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\AutoRegistroNegocioController;
use App\Http\Controllers\BandejaPendientesController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CierreTemporalController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\ModeradorController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\NegocioPublicoController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PerfilController;
use App\Http\Controllers\ProductoController;
use App\Http\Middleware\EnsureNegocioAbierto;
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

        // US-03: lista y estado de negocios. US-02a: alta de negocio
        Route::prefix('negocios')->name('negocios.')->group(function () {
            Route::get('/', [NegocioController::class, 'index'])->name('index');
            Route::get('/crear', [AltaNegocioController::class, 'create'])->name('create');
            Route::post('/', [AltaNegocioController::class, 'store'])->name('store');

            // US-26b: bandeja de pendientes
            Route::get('/pendientes', [BandejaPendientesController::class, 'index'])->name('pendientes');
            Route::patch('{negocio}/aprobar', [BandejaPendientesController::class, 'aprobar'])->name('aprobar');
            Route::patch('{negocio}/rechazar', [BandejaPendientesController::class, 'rechazar'])->name('rechazar');

            Route::patch('{negocio}/suspender', [NegocioController::class, 'suspender'])->name('suspender');
            Route::patch('{negocio}/reactivar', [NegocioController::class, 'reactivar'])->name('reactivar');
        });
    });
});

// Todo lo de un negocio vive en su subdominio.
Route::domain('{subdominio}.'.$dominio)->middleware('negocio')->group(function () {
    // US-02b: página provisional hasta que exista el menú (US-11)
    Route::get('/', [NegocioPublicoController::class, 'mostrar'])->name('negocio.publico');

    // US-19b: ruta de prueba. Cuando exista el envío real de pedidos, el middleware va en esa ruta.
    Route::get('/prueba/enviar-pedido', fn () => 'Pedido enviado (ruta de prueba)')
        ->middleware(EnsureNegocioAbierto::class)->name('prueba.enviar-pedido');

    Route::middleware(['auth', 'negocio.propio'])->prefix('panel')->group(function () {
        Route::get('/', [PanelController::class, 'negocio'])->name('panel.negocio');

        // US-23: equipo de trabajo del dueño
        Route::middleware('permiso:gestionar_equipo')->group(function () {
            Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
            Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
            Route::patch('/equipo/{empleado}/desactivar', [EquipoController::class, 'desactivar'])->name('equipo.desactivar');
        });

        Route::middleware('permiso:editar_perfil')->group(function () {
            // US-05: perfil del negocio
            Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
            Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');

            // US-06: categorías del menú
            Route::prefix('categorias')->name('categorias.')->group(function () {
                Route::get('/', [CategoriaController::class, 'index'])->name('index');
                Route::post('/', [CategoriaController::class, 'store'])->name('store');
                Route::put('{categoria}', [CategoriaController::class, 'update'])->name('update');
                Route::delete('{categoria}', [CategoriaController::class, 'destroy'])->name('destroy');
                Route::patch('{categoria}/subir', [CategoriaController::class, 'subir'])->name('subir');
                Route::patch('{categoria}/bajar', [CategoriaController::class, 'bajar'])->name('bajar');
            });

            // US-07a: crear y editar productos del menú
            Route::prefix('productos')->name('productos.')->group(function () {
                Route::get('/crear', [ProductoController::class, 'create'])->name('create');
                Route::post('/', [ProductoController::class, 'store'])->name('store');
                Route::get('{producto}/editar', [ProductoController::class, 'edit'])->name('edit');
                Route::put('{producto}', [ProductoController::class, 'update'])->name('update');
            });

            // US-19a: horarios de atención
            Route::prefix('horarios')->name('horarios.')->group(function () {
                Route::get('/', [HorarioController::class, 'index'])->name('index');
                Route::post('/', [HorarioController::class, 'store'])->name('store');
                Route::delete('{horario}', [HorarioController::class, 'destroy'])->name('destroy');
            });

            // US-19b: cierre temporal
            Route::prefix('cierre-temporal')->name('cierre-temporal.')->group(function () {
                Route::get('/', [CierreTemporalController::class, 'index'])->name('index');
                Route::patch('/cerrar', [CierreTemporalController::class, 'cerrar'])->name('cerrar');
                Route::patch('/reabrir', [CierreTemporalController::class, 'reabrir'])->name('reabrir');
            });
        });

        // Productos de una categoría (solo lectura, hasta que exista el menú)
        Route::get('/categorias/{categoria}', [PanelController::class, 'categoria'])->name('panel.negocio.categoria');

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
