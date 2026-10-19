<?php

use App\Http\Controllers\EquipoController;
use Illuminate\Support\Facades\Route;

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