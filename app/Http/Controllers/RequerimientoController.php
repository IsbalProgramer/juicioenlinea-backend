<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Helpers\FolioHelper;
use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\HistorialEstadoRequerimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use \Illuminate\Database\QueryException;
use Exception;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\PermisosApiService;

class RequerimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('secretario'));
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }

            // Verificar y actualizar el estado de los requerimientos expirados
            $requerimientos = Requerimiento::where('idSecretario', $idGeneral)->get();

            foreach ($requerimientos as $requerimiento) {

                $estadoFinal = $requerimiento->historial->last()->idCatEstadoRequerimientos ?? null;
                $fechaLimite = $requerimiento->fechaLimite;
                $fechaActual = now();

                if (($fechaLimite < $fechaActual) && $estadoFinal == 1) {
                    $this->estadoRequerimientoExpiro($requerimiento, $request, $permisosApiService);
                }
            };

            // Listar los requerimientos con sus relaciones
            $requerimientos = Requerimiento::with([
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento'
            ])->where('idSecretario', $idGeneral)->get();

            return response()->json([
                'status' => 200,
                'message' => "Listado de requerimientos",
                'data' => $requerimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de requerimientos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, PermisosApiService $permisosApiService)
    {

        // Validar los datos de la petición
        $validator = Validator::make($request->all(), [

            //Validaciones del requerimiento
            'idExpediente' => 'required|string',
            'descripcion' => 'required|string',
            'idAbogado' => 'required|integer',
            'fechaLimite' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $fechaLimite = Carbon::parse($value)->endOfDay(); // <--- Añade esto
                    if ($fechaLimite->lte(now())) {
                        $fail('La fecha límite debe ser posterior a la fecha actual.');
                    }
                }

            ],

            'documentoAcuerdo' => 'required|file|mimes:pdf,doc,docx',
        ]);

        // Si la validación falla, devolver un error 422
        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $errorMessage = implode(', ', $errors);
            return response()->json([
                'status' => 422,
                'message' => '' . $errorMessage,
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('secretario'));
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }


            //Api para subir un archivo 
            $apiDocumento = 'https://api.tribunaloaxaca.gob.mx/NasApi/api/Nas';

            $documentoAcuerdo = $request->file('documentoAcuerdo');
            $nombreOriginal = pathinfo($documentoAcuerdo->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $documentoAcuerdo->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $nuevoNombre = "{$nombreOriginal}_{$timestamp}.{$extension}";

            // Ruta para almacenamiento local si lo necesitas
            $documentoAcuerdo->storeAs('acuerdos', $nuevoNombre);

            // Construcción de la ruta
            $expediente = explode('/', $request->idExpediente);
            $expedienteRuta = $expediente[1] . '/' . $expediente[0];

            $ruta = "PERICIALES/JUZGADOS/{$expedienteRuta}/REQUERIMIENTOS/ACUERDOS";

            // Enviar el archivo como multipart/form-data
            $response = Http::withToken($request->bearerToken())
                ->attach(
                    'file',                             // nombre del campo
                    file_get_contents($documentoAcuerdo), // contenido del archivo
                    $nuevoNombre                        // nombre del archivo
                )
                ->post($apiDocumento, [
                    'path' => $ruta
                ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al subir el documento a la API.',
                    'error' => $response->json(),
                ], 500);
            }

            // Guardar el documento 
            $documento = new Documento();
            $documento->idCatTipoDocumento = 129;
            $documento->idExpediente = $request->idExpediente;
            $documento->folio = FolioHelper::generarFolio($request->idExpediente);
            $documento->documento = $ruta . '/' . $nuevoNombre;
            $documento->save();

            // Obtener el ID del documento recién creado
            $documentoID = $documento->idDocumento ?? Documento::latest('idDocumentoAcuerdo')->first()->idDocumento;
            if (!$documentoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'idSecretario' => $idGeneral, //ASIGNACION DEL USUARIO
                'usuarioSecretario' => $usr,
                'idDocumentoAcuerdo' => $documentoID,
                'fechaLimite' => Carbon::parse($request->fechaLimite)->endOfDay(),
                'idAbogado' => $request->idAbogado,

            ]);

            // Obtener el ID del requerimiento recién creado
            $requerimientoID = $requerimiento->idRequerimiento ?? Requerimiento::latest('idRequerimiento')->first()->idRequerimiento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$requerimientoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimientoID,
                'idExpediente' => $request->idExpediente,
                'idUsuario' =>  $idGeneral, //ASIGNACION DEL USUARIO
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Documento guardado y requerimiento creado con referencia al documento',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documento_id' => $documentoID,
                    'historial' => $historial,
                    'documento' => $documento
                ]
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error en la base de datos al crear el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error al crear el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($idRequerimiento)
    {
        try {
            $requerimiento = Requerimiento::with([
                'documentoAcuerdo.catTipoDocumento',
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento',
                'documentosRequerimiento.catTipoDocumento',
                'documentoAcuse.catTipoDocumento',
                'documentoOficioRequerimiento.catTipoDocumento',
            ])->findOrFail($idRequerimiento);

            //Acuerdo
            $documentoAcuerdo = $requerimiento->documentoAcuerdo;

            if ($documentoAcuerdo) {
                $tipoDocumento = $documentoAcuerdo->idCatTipoDocumento;
                if ($tipoDocumento == -1) {
                    $documentoAcuerdo->nombre = $documentoAcuerdo->nombre;
                } elseif ($documentoAcuerdo->catTipoDocumento) {
                    $documentoAcuerdo->nombre = $documentoAcuerdo->catTipoDocumento->descripcion;
                }
                unset($documentoAcuerdo->catTipoDocumento);
            }

            //Acuse
            $documentoOficioRequerimiento = $requerimiento->documentoOficioRequerimiento;
            if ($documentoOficioRequerimiento) {
                $tipoDocumento = $documentoOficioRequerimiento->idCatTipoDocumento;
                if ($tipoDocumento == -1) {
                    $documentoOficioRequerimiento->nombre = $documentoOficioRequerimiento->nombre;
                } elseif ($documentoOficioRequerimiento->catTipoDocumento) {
                    $documentoOficioRequerimiento->nombre = $documentoOficioRequerimiento->catTipoDocumento->descripcion;
                }
                unset($documentoOficioRequerimiento->catTipoDocumento);
            }

            //Acuse
            $documentoAcuse = $requerimiento->documentoAcuse;
            if ($documentoAcuse) {
                $tipoDocumento = $documentoAcuse->idCatTipoDocumento;
                if ($tipoDocumento == -1) {
                    $documentoAcuse->nombre = $documentoAcuse->nombre;
                } elseif ($documentoAcuse->catTipoDocumento) {
                    $documentoAcuse->nombre = $documentoAcuse->catTipoDocumento->descripcion;
                }
                unset($documentoAcuse->catTipoDocumento);
            }

            //Documentos Requerimiento
            foreach ($requerimiento->documentosRequerimiento as $documento) {
                if ($documento->idCatTipoDocumento == -1) {
                    $documento->nombre = $documento->nombre;
                } elseif ($documento->catTipoDocumento) {
                    $documento->nombre = $documento->catTipoDocumento->descripcion;
                } else {
                    $documento->nombre = null;
                }

                unset($documento->catTipoDocumento);
            }

            return response()->json([
                'status' => 200,
                'message' => "Detalle del requerimiento",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener el requerimiento',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requerimiento $requerimiento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requerimiento $requerimiento) {}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requerimiento $requerimiento)
    {
        //
    }


    public function subirRequerimiento(Requerimiento $requerimiento, Request $request, PermisosApiService $permisosApiService)
    {
        // $fechaLimite = Carbon::parse($requerimiento->fechaLimite)->endOfDay();

        // if ($fechaLimite->isPast()) {
        //     return response()->json([
        //         'status' => 400,
        //         'message' => 'No se puede subir el requerimiento porque la fecha límite ya ha pasado.',
        //     ], 400);
        // }

        //Verificar si ya se subió un documento para este requerimiento
        $documentosExistentes = DB::table('documento_requerimiento')
            ->where('idRequerimiento', $requerimiento->idRequerimiento)
            ->exists();

        if ($documentosExistentes) {
            return response()->json([
                'status' => 400,
                'message' => 'Ya se han subido documentos para este requerimiento.',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'documentoOficioRequerimiento' => 'required|file|mimes:pdf,doc,docx',
            'documentoRequerimiento' => 'required|array|min:1',
            'documentoRequerimiento.*' => 'required|file|mimes:pdf,doc,docx',
            'idCatTipoDocumento' => 'required|array|min:1',
            'idCatTipoDocumento.*' => 'required|integer',
            'nombre' => 'nullable|array|min:1',
            'nombre.*' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            DB::beginTransaction();


            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('abogado'));
            });

            if (!$tienePerfilAbogado) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }


            $idAbogado = $requerimiento->idAbogado;

            if ($idGeneral != $idAbogado) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Requerimiento no asignado',
                ], 400);
            }

            $documentos = [];

            //Api para subir un archivo 
            $apiDocumento = 'https://api.tribunaloaxaca.gob.mx/NasApi/api/Nas';

            //oficio requerimiento
            $documentoOficioRequerimiento = $request->file('documentoOficioRequerimiento');
            $nombreOriginal = pathinfo($documentoOficioRequerimiento->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $documentoOficioRequerimiento->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $nuevoNombre = "{$nombreOriginal}_{$timestamp}.{$extension}";

            // Ruta para almacenamiento local si lo necesitas
            $documentoOficioRequerimiento->storeAs('oficioRequerimiento', $nuevoNombre);

            // Construcción de la ruta
            $expediente = explode('/', $requerimiento->idExpediente);
            $expedienteRuta = $expediente[1] . '/' . $expediente[0];

            $ruta = "PERICIALES/JUZGADOS/{$expedienteRuta}/REQUERIMIENTOS/OFICIOREQUERIMIENTO";

            // Enviar el archivo como multipart/form-data
            $response = Http::withToken($request->bearerToken())
                ->attach(
                    'file',                             // nombre del campo
                    file_get_contents($documentoOficioRequerimiento), // contenido del archivo
                    $nuevoNombre                        // nombre del archivo
                )
                ->post($apiDocumento, [
                    'path' => $ruta
                ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al subir el documento a la API.',
                    'error' => $response->json(),
                ], 500);
            }

            // Guardar el documento en la base de datos
            $documentoR = new Documento();
            $documentoR->idCatTipoDocumento = 130;
            $documentoR->idExpediente = $requerimiento->idExpediente;
            $documentoR->folio = FolioHelper::generarFolio($requerimiento->idExpediente);
            $documentoR->documento = $ruta . '/' . $nuevoNombre;
            $documentoR->save();

            $requerimiento->idDocumentoOficioRequerimiento = $documentoR->idDocumento;
            $requerimiento->save();

            $documentos[] = $documentoR;

            //archivos requeridos
            $archivos = $request->file('documentoRequerimiento');
            $expediente = explode('/', $requerimiento->idExpediente);
            $expedienteRuta = $expediente[1] . '/' . $expediente[0];
            $ruta = "PERICIALES/JUZGADOS/{$expedienteRuta}/REQUERIMIENTOS/TRAMITESRECIBIDOS";


            foreach ($archivos as $index => $archivo) {
                $nombreOriginal = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
                $extension = $archivo->getClientOriginalExtension();
                $timestamp = now()->format('Ymd_His');
                $nuevoNombre = "{$nombreOriginal}_{$timestamp}.{$extension}";

                // Guardar copia local
                $archivo->storeAs('requerimiento', $nuevoNombre);

                // Subir al NAS
                $response = Http::withToken($request->bearerToken())
                    ->attach('file', file_get_contents($archivo), $nuevoNombre)
                    ->post($apiDocumento, ['path' => $ruta]);

                if ($response->failed()) {
                    return response()->json([
                        'status' => 500,
                        'message' => 'Error al subir el documento a la API.',
                        'error' => $response->json(),
                    ], 500);
                }

                // Crear documento en la BD
                $documento = new Documento();
                $documento->documento = $ruta . '/' . $nuevoNombre;

                // Asignar idCatTipoDocumento y nombre si aplica
                $idTipo = $request->idCatTipoDocumento[$index] ?? null;
                $nombre = $request->nombre[$index] ?? null;

                if ($idTipo == -1) {
                    $documento->idCatTipoDocumento = -1;
                    $documento->nombre = $nombre;
                } elseif ($idTipo > 0) {
                    $documento->idCatTipoDocumento = $idTipo;
                }

                $documento->save();

                // Guardar en tabla pivote documento_requerimiento
                DB::table('documento_requerimiento')->insert([
                    'idRequerimiento' => $requerimiento->idRequerimiento,
                    'idDocumento' => $documento->idDocumento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $documentos[] = $documento;
            }

            $apiDatos = 'https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/DatosUsuario';
            $response1 = Http::withToken($request->bearerToken())
                ->timeout(60) // 60 segundos de espera
                ->post("$apiDatos?Usuario=" . $requerimiento->usuarioSecretario);

            if ($response1->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al consultar los datos del usuario secretario.',
                    'error' => $response1->body(),
                ], 500);
            }

            $token = $request->bearerToken();
            $datosUsuarioSecretario = AuthHelper::obtenerNombreUsuarioDesdeApi($requerimiento->usuarioSecretario, $token);
            $datosUsuarioAbogado = AuthHelper::obtenerNombreUsuarioDesdeApi($usr, $token);


            // Crear HTML del acuse
            //<p><strong>Requerimiento:</strong> {$requerimiento->idRequerimiento}</p>
            $html = "
                    <h2 style='text-align:center;'>ACUSE DE RECIBO</h2>

                    <p><strong>Expediente:</strong> {$requerimiento->idExpediente}</p>
                    <p><strong>Descripcion:</strong> {$requerimiento->descripcion}</p>
                    <p><strong>Secretario:</strong> {$datosUsuarioSecretario}</p>
                    <p><strong>Abogado:</strong> {$datosUsuarioAbogado}</p>
                    <p><strong>Fecha de recepción:</strong> " . now()->format('Y-m-d H:i:s') . "</p>
                    <h4>Documentos entregados:</h4>
                    <ul>
                    ";

            foreach ($documentos as $doc) {
                $nombre = isset($doc->nombre) && !empty($doc->nombre) ? $doc->nombre : 'Documento sin nombre';
                if (isset($doc->idCatTipoDocumento) && $doc->idCatTipoDocumento > 0) {
                    // Buscar el nombre del tipo de documento
                    $tipo = DB::table('cat_tipo_documentos')
                        ->where('idCatTipoDocumento', $doc->idCatTipoDocumento)
                        ->value('descripcion');

                    if (!$tipo) {
                        Log::warning("Tabla 'cat_tipo_documento' no encontrada o sin datos para idCatTipoDocumento: {$doc->idCatTipoDocumento}");
                        $tipo = 'Sin tipo';
                    }
                } elseif (isset($doc->idCatTipoDocumento) && $doc->idCatTipoDocumento == -1) {
                    $tipo = $nombre; // Usar el nombre del documento si el tipo es -1
                } else {
                    $tipo = 'Sin tipo';
                }
                $html .= "<li>{$tipo}</li>";
            }

            $html .= "</ul>";



            // Generar PDF del acuse
            $pdf = Pdf::loadHTML($html);

            // Definir nombre y ruta
            $timestamp = now()->format('Ymd_His');
            $nuevoNombre = "acuse_recepcion_{$timestamp}.pdf";

            // Guardar local si quieres (opcional)
            Storage::put("acuses/{$nuevoNombre}", $pdf->output());

            // Ruta NAS
            $expediente = explode('/', $requerimiento->idExpediente);
            $expedienteRuta = $expediente[1] . '/' . $expediente[0];
            $ruta = "PERICIALES/JUZGADOS/{$expedienteRuta}/REQUERIMIENTOS/ACUSES";

            // Subir PDF generado directamente al NAS
            $response = Http::withToken($request->bearerToken())
                ->attach(
                    'file',
                    $pdf->output(),
                    $nuevoNombre
                )
                ->post($apiDocumento, [
                    'path' => $ruta
                ]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al subir el documento a la API.',
                    'error' => $response->json(),
                ], 500);
            }

            // Guardar en base de datos
            $acuseDocumento = new Documento();
            $acuseDocumento->idCatTipoDocumento = 131;
            $acuseDocumento->documento = $ruta . '/' . $nuevoNombre;
            $acuseDocumento->save();

            // Asociar a requerimiento
            $requerimiento->idDocumentoAcuse = $acuseDocumento->idDocumento;
            $requerimiento->usuarioAbogado = $usr;
            $requerimiento->save();

            // Agregar a lista de documentos
            $documentos[] = $acuseDocumento;


            // Historial general del requerimiento
            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $request->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 3, // REQUERIMIENTO SUBIDO Y ENVIADO
            ]);

            $requerimiento->save();
            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Documentos subidos correctamente',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documentos' => $documentos,
                    'historial' => $historial,

                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al subir el requerimiento: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al subir el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Descargar un documento almacenado en base64
     */

    // admitir
    public function admitirRequerimiento(Request $request, Requerimiento $requerimiento, PermisosApiService $permisosApiService)
    {
        try {
            DB::beginTransaction();

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('secretario'));
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

            // Crear historial
            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 4
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento admitido correctamente.',
                'data' => [
                    'requerimiento' => $requerimiento,
                    // 'documento_id' => $documento->idDocumento,
                    // 'documento' => $documento,
                    'historial' => $historial,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al admitir requerimiento: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al admitir el requerimiento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //denegar requerimiento
    public function denegarRequerimiento(Request $request, Requerimiento $requerimiento, PermisosApiService $permisosApiService)
    {
        try {
            DB::beginTransaction();


            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('secretario'));
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

            // Define que el que creo sea el que admita o deniegue
            $idSecretario = $requerimiento->idSecretario;

            if ($idGeneral != $idSecretario) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Requerimiento no asignado',
                ], 400);
            }

            // Validar el archivo
            $validator = Validator::make($request->all(), [
                'descripcionRechazo' => 'required|string',
            ]);

            if ($validator->fails()) {
                $errors = $validator->messages()->all();
                return response()->json([
                    'status' => 422,
                    'message' => implode(', ', $errors),
                ], 422);
            }


            $requerimiento->descripcionRechazo = $request->descripcionRechazo;
            $requerimiento->save();


            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 5,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento denegado correctamente.',
                'data' => [
                    'requerimiento' => $requerimiento,
                    // 'documento_id' => $documento->idDocumento,
                    // 'documento' => $documento,
                    'historial' => $historial,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al denegar requerimiento: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al denegar el requerimiento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    //Este metodo se utiliza para verificar si el requerimiento ya paso su
    // fecha limite e insertar el estado correspondiente
    //en el historial de requerimiento

    public function estadoRequerimientoExpiro(Requerimiento $requerimiento, Request $request, PermisosApiService $permisosApiService)
    {
        if (!$requerimiento) {
            return response()->json([
                'status' => 404,
                'message' => 'Requerimiento no encontrado.',
            ], 404);
        }

        $fechaLimite = Carbon::parse($requerimiento->fechaLimite);

        if ($fechaLimite->lt(now())) {
            // Verificar si ya existe un registro en el historial con estado "expirado"
            $existeHistorial = HistorialEstadoRequerimiento::where('idRequerimiento', $requerimiento->idRequerimiento)
                ->where('idCatEstadoRequerimientos', 2) // 2 es el estado "expirado"
                ->exists();

            if ($existeHistorial) {
                return response()->json([
                    'status' => 200,
                    'message' => 'El requerimiento ya fue marcado como expirado previamente.',
                ], 200);
            }

            try {
                DB::beginTransaction();

    // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

                        if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }
            
            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

           $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('secretario'));
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

                // Registrar el cambio en el historial
                $historial = HistorialEstadoRequerimiento::create([
                    'idRequerimiento' => $requerimiento->idRequerimiento,
                    'idExpediente' => $requerimiento->idExpediente,
                    'idUsuario' => $idGeneral,
                    'idCatEstadoRequerimientos' => 2, // 2 expirado
                ]);

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => 'El requerimiento ha expirado y se ha registrado en el historial.',
                    'data' => [
                        'requerimiento' => $requerimiento,
                        'historial' => $historial,
                    ],
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al actualizar el estado del requerimiento.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'La fecha límite del requerimiento aún no ha pasado.',
        ], 200);
    }


    public function listarRequerimientosAbogado(Request $request, PermisosApiService $permisosApiService)
    {
        try {


                        // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

                        if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral o Usr del token',
                ], 400);
            }
            
            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            if (!$idGeneral || !$usr) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $datosUsuario['idGeneral'], 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario del token',
                ], 400);
            }
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

           $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim('abogado'));
            });

            if (!$tienePerfilAbogado) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }


            // Verificar y actualizar el estado de los requerimientos expirados
            $requerimientos = Requerimiento::where('idAbogado', $idGeneral)->get();

            // foreach ($requerimientos as $requerimiento) {
            //     $fechaLimite = Carbon::parse($requerimiento->fechaLimite);
            //     $estadoFinal = $requerimiento->historial->last()->idCatEstadoRequerimientos ?? null;

            //     if ($fechaLimite->lt(now()) && $estadoFinal == 1) {
            //         $this->estadoRequerimientoExpiro($requerimiento, $request);
            //     }
            // }

            foreach ($requerimientos as $requerimiento) {

                $fechaLimite = $requerimiento->fechaLimite;
                $fechaActual = now();

                // Obtener el último estado del requerimiento
                $estadoFinal = $requerimiento->historial->last()->idCatEstadoRequerimientos ?? null;

                if (($fechaLimite >= $fechaActual) && $estadoFinal == 1) {
                    $this->estadoRequerimientoExpiro($requerimiento, $request, $permisosApiService);
                }
            }

            // Listar los requerimientos con un identificador único para el abogado
            $requerimientos = Requerimiento::with([
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento'
            ])->where('idAbogado', $idGeneral)->get();

            $requerimientos = $requerimientos->map(function ($requerimiento) {
                $requerimiento->idRequerimientoAbogado = 'ABOG-' . $requerimiento->idRequerimiento;
                return $requerimiento;
            });

            return response()->json([
                'status' => 200,
                'message' => "Listado de requerimientos",
                'data' => $requerimientos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de requerimientos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
