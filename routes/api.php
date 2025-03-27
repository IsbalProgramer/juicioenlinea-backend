<?php

use App\Http\Controllers\Catalogos\CatEstadoInicioController;
use App\Http\Controllers\Catalogos\CatGenerosController;
use App\Http\Controllers\Catalogos\CatMateriasController;
use App\Http\Controllers\Catalogos\CatPartesController;
use App\Http\Controllers\Catalogos\CatViasController;
use App\Http\Controllers\InicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\RequerimientoController;
use Illuminate\Routing\RouteGroup;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::prefix('Inicio')->group(function(){
    Route::post('CrearInicio',[InicioController::class,'store']);
    Route::get('ListadoInicios',[InicioController::class,'index']);
    Route::get('DetalleInicio/{inicio}',[InicioController::class,'show']);    
});

Route::prefix('Catalogo')->group(function(){
    Route::get('Vias', [CatViasController::class,'index']);
    Route::get('Partes', [CatPartesController::class,'index']);
    Route::get('Materias', [CatMateriasController::class,'index']);
    Route::get('Generos', [CatGenerosController::class,'index']);
    Route::get('EstadosInicio', [CatEstadoInicioController::class,'index']);
});

// documentos 
Route::post('documento',[DocumentoController::class,'store']); //insetar docuemento
Route::get('documento',[DocumentoController::class,'index']); // obtiene documentos

//Requerimiento -- catalogo
Route::post('catReq',[RequerimientoController::class,'store']);  // inserta catalogo de requerimiento

//Requerimiento -- requerimiento 
Route::post('requerimiento',[RequerimientoController::class,'store']); // inserta requerimiento



