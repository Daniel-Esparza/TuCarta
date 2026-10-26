<?php

use App\Http\Controllers\NegocioController;
use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ModeradorController;

Route::view('/', 'landing')->name('landing');

/*
US-23: Equipo de trabajo del dueño (alta y desactivación de empleados)
*/

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