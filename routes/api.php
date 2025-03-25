<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\RequerimientoController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Route::get('inicio', [InicioController::class, 'index']); // Método para manejar GET

Route::post('inicio',[InicioController::class,'store']);
// documentos 
Route::post('documento',[DocumentoController::class,'store']); //insetar docuemento
Route::get('documento',[DocumentoController::class,'index']); // obtiene documentos

//Requerimiento -- catalogo
Route::post('catReq',[RequerimientoController::class,'store']);  // inserta catalogo de requerimiento
Route::get('catReq',[RequerimientoController::class,'index']);  // obtiene catalogo de requerimiento