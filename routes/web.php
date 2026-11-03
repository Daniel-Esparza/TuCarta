<?php

use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\EquipoController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\ModeradorController;
use App\Http\Controllers\NegocioController;
use App\Http\Controllers\NegocioPublicoController;
use App\Http\Controllers\PerfilController;
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

// US-06: Categorías del menú
Route::middleware('permiso:editar_perfil')->prefix('categorias')->name('categorias.')->group(function () {
    Route::get('/', [CategoriaController::class, 'index'])->name('index');
    Route::post('/', [CategoriaController::class, 'store'])->name('store');
    Route::put('{categoria}', [CategoriaController::class, 'update'])->name('update');
    Route::delete('{categoria}', [CategoriaController::class, 'destroy'])->name('destroy');
    Route::patch('{categoria}/subir', [CategoriaController::class, 'subir'])->name('subir');
    Route::patch('{categoria}/bajar', [CategoriaController::class, 'bajar'])->name('bajar');
});

// US-19a: horarios de atencion del negocio (dueño).
Route::middleware('permiso:editar_perfil')->prefix('horarios')->name('horarios.')->group(function () {
    Route::get('/', [HorarioController::class, 'index'])->name('index');
    Route::post('/', [HorarioController::class, 'store'])->name('store');
    Route::delete('{horario}', [HorarioController::class, 'destroy'])->name('destroy');
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

// Ruta temporal para iniciar sesión como dueño de negocio
Route::get('/login-local', function () {
    $dueno = \App\Models\Usuario::where('correo', 'dueno@tacosjuan.com')->first();
    auth()->login($dueno);
    return redirect('/equipo');
});

// US-26a: auto-registro publico de negocios (sin sesion).
Route::get('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'create'])->name('auto-registro.create');
Route::post('/registrar-negocio', [\App\Http\Controllers\AutoRegistroNegocioController::class, 'store'])->name('auto-registro.store');

// TEMPORAL: entrar sin login para tomar capturas. BORRAR ANTES DEL COMMIT.
Route::get('/entrar-prueba/{email}', function (string $email) {
    \Illuminate\Support\Facades\Auth::login(\App\Models\Usuario::where('email', $email)->firstOrFail());

    return redirect('/horarios');
});

Route::middleware('permiso:editar_perfil')->group(function () {
    Route::get('/perfil', [PerfilController::class, 'edit'])->name('perfil.edit');
    Route::patch('/perfil', [PerfilController::class, 'update'])->name('perfil.update');
});