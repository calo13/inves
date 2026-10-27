<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ExportController;
use App\Livewire\Panel;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::view('/ingresar', 'auth.ingresar')->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:10,1');
});
Route::middleware('auth')->group(function () {
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');
    Route::post('/contrasena', [AuthController::class, 'password'])->name('password.change');
    Route::get('/exportar/{formato}', [ExportController::class, 'exportar'])->whereIn('formato', ['xlsx', 'pdf'])->name('exportar');
    Route::get('/', Panel::class)->name('dashboard');
    Route::get('/{modulo}', Panel::class)->whereIn('modulo', ['tickets', 'recursos', 'simulador', 'calendario', 'importacion', 'calidad', 'parametros', 'pruebas'])->name('modulo');
});
