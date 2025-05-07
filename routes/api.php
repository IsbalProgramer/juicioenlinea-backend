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
use App\Http\Controllers\PreRegistroController;
use App\Http\Controllers\RequerimientoController;
use Illuminate\Routing\RouteGroup;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Http\Middleware\EnsureTokenIsValid;
use App\Http\Middleware\VerifyJwtToken;

use Illuminate\Routing\Route as RoutingRoute;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

// Route::post('/login', function (Request $request) {
//     $credentials = $request->only('email', 'password');


//     if (!Auth::attempt($credentials)) {
//         return response()->json(['message' => 'Credenciales inválidas'], 401);
//     }

//     $user = Auth::user();
//     $token = $user->createToken('token-personal')->plainTextToken;

//     return response()->json(['token' => $token]);
// });

Route::prefix('Inicio')->group(function () {
    Route::post('CrearInicio', [PreRegistroController::class, 'store'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoInicios', [PreRegistroController::class, 'index'])->middleware(VerifyJwtToken::class);
    Route::get('DetalleInicio/{idInicio}', [PreRegistroController::class, 'show']);
    Route::get('Documento/{idDocumento}', [DocumentoController::class, 'show']); // obtiene documentos

});

Route::prefix('Catalogo')->group(function () {
    Route::get('Vias', [CatViasController::class, 'index']);
    Route::get('Partes', [CatPartesController::class, 'index']);
    Route::get('Materias', [CatMateriasController::class, 'index']);
    Route::get('Generos', [CatGenerosController::class, 'index']);
    Route::get('EstadosInicio', [CatEstadoInicioController::class, 'index']);
});


//Requerimiento -- requerimiento 
// Route::post('requerimiento',[RequerimientoController::class,'store']); // inserta requerimiento
// Route::get('/requerimiento/{id}/descargar-documento', [RequerimientoController::class, 'descargarDocumentoPorRequerimiento']); // obtiene requerimientos
//Route::get('requerimiento/{requerimiento}', [RequerimientoController::class, 'show']); // obtiene requerimientos


Route::prefix('Requerimiento')->group(function () {
    Route::post('CrearRequerimiento', [RequerimientoController::class, 'store'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoRequerimientos', [RequerimientoController::class, 'index'])->middleware(VerifyJwtToken::class);
    Route::get('ListadoRequerimientosAbogados', [RequerimientoController::class, 'listarRequerimientosAbogado'])->middleware(VerifyJwtToken::class);
    Route::get('DetalleRequerimiento/{requerimiento}', [RequerimientoController::class, 'show'])->middleware(VerifyJwtToken::class);
    Route::get('VerDocumento/{id}', [RequerimientoController::class, 'verDocumento'])->middleware(VerifyJwtToken::class);
    Route::post('SubirRequerimiento/{requerimiento}', [RequerimientoController::class, 'subirRequerimiento'])->middleware(VerifyJwtToken::class);
    Route::get('ListarAcuerdo/{requerimiento}', [RequerimientoController::class, 'listarAcuerdo'])->middleware(VerifyJwtToken::class);
    Route::get('ListarDocumentosRequerimiento/{requerimiento}', [RequerimientoController::class, 'listarDocumentosRequerimiento'])->middleware(VerifyJwtToken::class);
    // Route::post('ActualizarRequerimiento/{requerimiento}',[RequerimientoController::class, 'actualizarDocumento']);
    // Route::post('EliminarRequerimiento/{requerimiento}',[RequerimientoController::class, 'eliminarDocumento']);
    Route::post('RequerimientoExpirado/{requerimiento}', [RequerimientoController::class, 'estadoRequerimientoExpiro'])->middleware(VerifyJwtToken::class);
    Route::post('AdmitirRequerimiento/{requerimiento}', [RequerimientoController::class, 'admitirRequerimiento'])->middleware(VerifyJwtToken::class);
    Route::post('DenegarRequerimiento/{requerimiento}', [RequerimientoController::class, 'denegarRequerimiento'])->middleware(VerifyJwtToken::class);
});
