<?php

use App\Http\Controllers\NegocioController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ModeradorController;
use App\Http\Controllers\NegocioPublicoController;
use Illuminate\Support\Facades\Route;

// US-02b: la direccion de cada negocio ({subdominio}.{dominio}) segun su estado.
// Va antes de la landing, que responde en cualquier host.
Route::domain('{subdominio}.'.config('tucarta.dominio'))
    ->get('/', [NegocioPublicoController::class, 'mostrar'])
    ->name('negocio.publico');

Route::view('/', 'landing')->name('landing');

Route::middleware('permiso:gestionar_equipo')->group(function () {
    Route::get('/equipo', [EquipoController::class, 'index'])->name('equipo.index');
    Route::post('/equipo', [EquipoController::class, 'store'])->name('equipo.store');
    Route::patch('/equipo/{empleado}/desactivar', [EquipoController::class, 'desactivar'])
        ->name('equipo.desactivar');
});

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

    Route::middleware('auth')->prefix('admin/moderadores')->name('moderadores.')->group(function () {
    Route::get('/', [ModeradorController::class, 'index'])->name('index');
    Route::post('/', [ModeradorController::class, 'store'])->name('store');
    Route::post('{moderador}/permisos', [ModeradorController::class, 'asignarPermiso'])->name('permisos.store');
    Route::delete('{moderador}/permisos/{asignacion}', [ModeradorController::class, 'quitarPermiso'])->name('permisos.destroy');
});



Route::middleware('auth')->prefix('admin/negocios')->name('negocios.')->group(function () {
    Route::get('/', [NegocioController::class, 'index'])->name('index');
    Route::patch('{negocio}/suspender', [NegocioController::class, 'suspender'])->name('suspender');
    Route::patch('{negocio}/reactivar', [NegocioController::class, 'reactivar'])->name('reactivar');
});
// US-26a: auto-registro publico de negocios (sin sesion).
Route::get('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'create'])->name('auto-registro.create');
Route::post('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'store'])->name('auto-registro.store');
