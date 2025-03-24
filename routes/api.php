<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\DocumentoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::get('inicio', [InicioController::class, 'index']); // Método para manejar GET

Route::post('inicio',[InicioController::class,'store']);
Route::post('documento',[DocumentoController::class,'store']);
