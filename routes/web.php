<?php

<<<<<<< HEAD
use App\Http\Controllers\CategoriaController;
=======
use App\Http\Controllers\PerfilController;
>>>>>>> origin/main
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\ModeradorController;
use App\Http\Controllers\NegocioPublicoController;
use Illuminate\Support\Facades\Route;



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

Route::middleware('permiso:editar_perfil')->prefix('categorias')->name('categorias.')->group(function () {
    Route::get('/', [CategoriaController::class, 'index'])->name('index');
    Route::post('/', [CategoriaController::class, 'store'])->name('store');
    Route::put('{categoria}', [CategoriaController::class, 'update'])->name('update');
    Route::delete('{categoria}', [CategoriaController::class, 'destroy'])->name('destroy');
    Route::patch('{categoria}/subir', [CategoriaController::class, 'subir'])->name('subir');
    Route::patch('{categoria}/bajar', [CategoriaController::class, 'bajar'])->name('bajar');
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
<<<<<<< HEAD
// Ruta temporal para iniciar sesión como dueño de negocio

Route::get('/login-local', function () {
    $dueno = \App\Models\Usuario::where('correo', 'dueno@tacosjuan.com')->first();
    auth()->login($dueno);
    return redirect('/equipo');
=======

Route::get('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'create'])->name('auto-registro.create');
Route::post('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'store'])->name('auto-registro.store');


Route::middleware('permiso:editar_perfil')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
>>>>>>> origin/main
});