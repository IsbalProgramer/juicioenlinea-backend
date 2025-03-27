<?php

use App\Http\Controllers\InicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\RequerimientoController;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('inicio',[InicioController::class,'store']);
// documentos 
Route::post('documento',[DocumentoController::class,'store']); //insetar docuemento
Route::get('documento',[DocumentoController::class,'index']); // obtiene documentos

//Requerimiento -- catalogo
Route::post('catReq',[RequerimientoController::class,'store']);  // inserta catalogo de requerimiento

//Requerimiento -- requerimiento 
Route::post('requerimiento',[RequerimientoController::class,'store']); // inserta requerimiento
Route::get('/requerimiento/{id}/descargar-documento', [RequerimientoController::class, 'descargarDocumentoPorRequerimiento']); // obtiene requerimientos



