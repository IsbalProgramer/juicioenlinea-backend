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
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Middleware\VerifyJwtToken;


Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::prefix('Inicio')->group(function(){
    Route::post('CrearInicio',[InicioController::class,'store'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoInicios',[InicioController::class,'index'])->middleware(VerifyJwtToken::class);
    Route::get('DetalleInicio/{idInicio}',[InicioController::class,'show']);    
    Route::get('Documento/{idDocumento}',[DocumentoController::class,'show']); // obtiene documentos

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
 


//Requerimiento -- requerimiento 
// Route::post('requerimiento',[RequerimientoController::class,'store']); // inserta requerimiento
// Route::get('/requerimiento/{id}/descargar-documento', [RequerimientoController::class, 'descargarDocumentoPorRequerimiento']); // obtiene requerimientos
 //Route::get('requerimiento/{requerimiento}', [RequerimientoController::class, 'show']); // obtiene requerimientos


 Route::prefix('Requerimiento')->group(function(){
    Route::post('CrearRequerimiento',[RequerimientoController::class,'store']);
    Route::get('ListadoRequerimientos',[RequerimientoController::class,'index']);
    Route::get('DetalleRequerimiento/{requerimiento}',[RequerimientoController::class,'show']);    
    Route::get('DescargarDocumento/{id}',[RequerimientoController::class,'descargarDocumentoPorRequerimiento']);
    Route::post('SubirRequerimiento/{requerimiento}',[RequerimientoController::class,'update']);
    Route::get('ListarDocumentosPorRequerimientos/{requerimiento}',[RequerimientoController::class, 'listarDocumentosPorRequerimiento']);
});
