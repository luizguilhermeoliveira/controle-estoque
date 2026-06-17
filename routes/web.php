<?php

use App\Http\Controllers\AlmoxarifadoController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\MaterialController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rotas de autenticação (acesso de visitantes)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

/*
|--------------------------------------------------------------------------
| Rotas protegidas (exigem autenticação)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/', function () {
        return view('dashboard', [
            'usuario' => auth()->user()->name,
        ]);
    })->name('dashboard');

    Route::resource('almoxarifados', AlmoxarifadoController::class)
        ->except('show')
        ->parameters(['almoxarifados' => 'almoxarifado']);

    Route::resource('materiais', MaterialController::class)
        ->except('show')
        ->parameters(['materiais' => 'material']);
});
