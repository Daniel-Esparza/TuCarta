<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'landing')->name('landing');

// Inicio de sesion (US-01). Pendiente: limitarlo al subdominio auth.* cuando exista el middleware de tenancy.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Pendiente (US-32): proteger por rol administrador.
    Route::view('/panel', 'admin.panel')->name('panel');
});
