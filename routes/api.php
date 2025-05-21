<?php

use App\Http\Controllers\Catalogos\CatEstadoInicioController;
use App\Http\Controllers\Catalogos\CatGenerosController;
use App\Http\Controllers\Catalogos\CatMateriasController;
use App\Http\Controllers\Catalogos\CatPartesController;
use App\Http\Controllers\Catalogos\CatTipoDocumentoController;
use App\Http\Controllers\Catalogos\CatViasController;
use App\Http\Controllers\InicioController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DocumentoController;
use App\Http\Controllers\ExpedienteController;
use App\Http\Controllers\PreRegistroController;
use App\Http\Controllers\RequerimientoController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Middleware\VerifyJwtToken;
use App\Models\Expediente;
use Illuminate\Routing\Route as RoutingRoute;


Route::prefix('Inicio')->group(function () {
    Route::post('CrearPreregistro', [PreRegistroController::class, 'store'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoPreregistros', [PreRegistroController::class, 'index'])->middleware(VerifyJwtToken::class);
    Route::get('DetallePreregistro/{idInicio}', [PreRegistroController::class, 'show'])->middleware(VerifyJwtToken::class);
    Route::get('Documento/{idDocumento}', [DocumentoController::class, 'show'])->middleware(VerifyJwtToken::class);
    Route::put('ActualizarPreregistro/{preRegistro}', [PreRegistroController::class, 'update'])->middleware(VerifyJwtToken::class);

});

Route::prefix('Catalogo')->group(function () {
    Route::get('Vias/{idCatMateria}', [CatViasController::class, 'show']); // Listar vías por idCatMateria
    Route::get('Partes', [CatPartesController::class, 'index']);
    Route::get('Materias', [CatMateriasController::class, 'index']);
    Route::get('Generos', [CatGenerosController::class, 'index']);
    Route::get('EstadosInicio', [CatEstadoInicioController::class, 'index']);
    Route::get('TipoDocumentos', [CatTipoDocumentoController::class, 'index']);
});



Route::prefix('Requerimiento')->group(function () {
    Route::post('CrearRequerimiento', [RequerimientoController::class, 'store'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoRequerimientos', [RequerimientoController::class, 'index'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoRequerimientosAbogados', [RequerimientoController::class, 'listarRequerimientosAbogado'])->middleware(VerifyJwtToken::class);
    Route::get('DetalleRequerimiento/{requerimiento}', [RequerimientoController::class, 'show'])->middleware(VerifyJwtToken::class);
    Route::post('SubirRequerimiento/{requerimiento}', [RequerimientoController::class, 'subirRequerimiento'])->middleware(VerifyJwtToken::class);
    Route::post('RequerimientoExpirado/{requerimiento}', [RequerimientoController::class, 'estadoRequerimientoExpiro'])->middleware(VerifyJwtToken::class);
    Route::post('AdmitirRequerimiento/{requerimiento}', [RequerimientoController::class, 'admitirRequerimiento'])->middleware(VerifyJwtToken::class);
    Route::post('DenegarRequerimiento/{requerimiento}', [RequerimientoController::class, 'denegarRequerimiento'])->middleware(VerifyJwtToken::class);
});

Route::prefix('ExpedienteAbogado')->group(
    function () {
        Route::get('Expedientes', [ExpedienteController::class, 'listarExpedientesDistintos']); //->middleware(VerifyJwtToken::class);
        Route::get('Expedientes/Abogados/{id}', [ExpedienteController::class, 'listarAbogadosPorExpediente']); //->middleware(VerifyJwtToken::class);
        Route::get('ListarExpedientesGenerales', [ExpedienteController::class, 'listarExpedientesGeneralesAbogados'])->middleware(VerifyJwtToken::class);
    }
);

Route::prefix('Documento')->group(
    function () {
    Route::get('VerDocumento/{id}', [DocumentoController::class, 'verDocumento'])->middleware(VerifyJwtToken::class);
    
});

Route::prefix('Expediente')->group(function () {
    Route::get('Listar', [ExpedienteController::class, 'index'])->middleware(VerifyJwtToken::class);
    Route::post('Asignar', [ExpedienteController::class, 'store'])->middleware(VerifyJwtToken::class);
});

