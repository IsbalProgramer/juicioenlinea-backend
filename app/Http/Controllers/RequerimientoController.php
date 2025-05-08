<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\HistorialEstadoRequerimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Database\QueryException;
use Exception;

class RequerimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request)
    {
        try {

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

            $requerimiento = Requerimiento::with([
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento'
            ])->WHERE('idSecretario', $idGeneral)->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de requerimientos",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de requeirimientos',
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
    public function store(Request $request)
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
                    if (Carbon::parse($value)->lte(now())) {
                        $fail('La fecha límite debe ser posterior a la fecha actual.');
                    }
                }
            ],

            'documentoAcuerdo' => 'required|file|mimes:pdf,doc,docx|max:2048',
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

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            //Validacion que el que crea sea solo el Secretario

            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

            // Guardar el documento en la base de datos
            $documento = new Documento();
            $documento->nombre = $request->file('documentoAcuerdo')->getClientOriginalName();
            $documento->documento = base64_encode(file_get_contents($request->file('documentoAcuerdo'))); // Convertir el archivo a base64
            $documento->save();

            // Obtener el ID del documento recién creado
            $documentoID = $documento->idDocumento ?? Documento::latest('idDocumentoAcuerdo')->first()->idDocumento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$documentoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'idSecretario' => $idGeneral, //ASIGNACION DEL USUARIO
                'idDocumentoAcuerdo' => $documentoID,
                'fechaLimite' => $request->fechaLimite,
                'idAbogado' => $request->idAbogado
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
                // 'idCatEstadoRequerimientos' => 1,
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

            $requerimiento = Requerimiento::with(
                'documentoAcuerdo:idDocumento',
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento',
                'documentoRequerimiento:idDocumento'
            )->findOrFail($idRequerimiento);
            return response()->json([
                'status' => 200,
                'message' => "Detalle del requerimiento",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontro el registro",
                'data' => $e
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


    public function subirRequerimiento(Request $request, Requerimiento $requerimiento)
    {

        $fechaLimite = $requerimiento->fechaLimite;

        if (Carbon::parse($fechaLimite)->lt(now())) {
            return response()->json([
                'status' => 400,
                'message' => 'No se puede modificar el requerimiento porque la fecha límite ya ha pasado.',
            ], 400);
        }

        // Verificar si ya se subió un documento para este requerimiento
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
            'documentoRequerimiento' => 'required|array|min:1',
            'documentoRequerimiento.*' => 'required|file|mimes:pdf,doc,docx|max:2048',
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

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }


            //Validar que el que suba solo sea el abogado 
            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
            });

            if (!$tienePerfilAbogado) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para subir un requerimiento. Se requiere el perfil de Abogado.',
                ], 403);
            }

            // Define $idAbogado before using it
            $idAbogado = $requerimiento->idAbogado;

            if ($idGeneral != $idAbogado) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Requerimiento no asignado',
                ], 400);
            }

            $documentos = [];
            $archivos = $request->file('documentoRequerimiento');

            foreach ($archivos as $index => $archivo) {
                $nombreArchivo = $archivo->getClientOriginalName();
                $contenidoBase64 = base64_encode(file_get_contents($archivo));

                $documento = new Documento();
                $documento->nombre = $nombreArchivo;
                $documento->documento = $contenidoBase64;
                $documento->save();

                // Relacionar con requerimiento en tabla pivote
                DB::table('documento_requerimiento')->insert([
                    'idRequerimiento' => $requerimiento->idRequerimiento,
                    'idDocumento' => $documento->idDocumento,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $documentos[] = $documento;
            }

            // Historial general del requerimiento
            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $request->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 3, //REQUERIMIENTO SUBIDO Y ENVIADO
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
        } catch (QueryException $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Algún folio de documento ya existe.',
                    'error' => $e->getMessage(),
                ], 400);
            }

            return response()->json([
                'status' => 400,
                'message' => 'Error en la base de datos',
                'error' => $e->getMessage(),
            ], 400);
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
    public function verDocumento($idDocumento)
    {
        try {
            $documento = Documento::select('nombre', 'documento')->findOrFail($idDocumento);
            return response()->json([
                'status' => 200,
                'message' => 'Detalle del documento',
                'data' => $documento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'No se encontró el registro',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    public function listarAcuerdo($idRequerimiento)
    {
        try {
            $requerimiento = Requerimiento::with(['documentoAcuerdo'])->findOrFail($idRequerimiento);

            $documentos = [];
            if ($requerimiento->documentoAcuerdo) {
                $requerimiento->documentoAcuerdo->tipo = 'Acuerdo';
                $documentos[] = $requerimiento->documentoAcuerdo;
            }

            return response()->json([
                'status' => 200,
                'message' => "Listado de documentos de acuerdo del requerimiento",
                'data' => $documentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarDocumentosRequerimiento($idRequerimiento)
    {
        try {
            $requerimiento = Requerimiento::with(['documentoRequerimiento'])->findOrFail($idRequerimiento);

            $documentos = [];
            if ($requerimiento->documentoRequerimiento) {
                foreach ($requerimiento->documentoRequerimiento as $documento) {
                    $documento->tipo = 'requerimiento';
                    $documentos[] = $documento;
                }
            }

            return response()->json([
                'status' => 200,
                'message' => "Listado de documentos de requerimiento",
                'data' => $documentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // admitir
    public function admitirRequerimiento(Request $request, Requerimiento $requerimiento)
    {
        try {
            DB::beginTransaction();
            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            //Validacion que el que crea sea solo el Secretario

            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para admitir un requerimiento. Se requiere el perfil de Secretario.',
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


            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 6,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento admitido correctamente.',
                'data' => [
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
    public function denegarRequerimiento(Request $request, Requerimiento $requerimiento)
    {
        try {
            DB::beginTransaction();

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            //Perfil secretario
            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            });

            if (!$tienePerfilSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para admitir un requerimiento. Se requiere el perfil de Secretario',
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


            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $idGeneral,
                'idCatEstadoRequerimientos' => 7,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento denegado correctamente.',
                'data' => [
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

    public function estadoRequerimientoExpiro(Requerimiento $requerimiento, Request $request)
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

                // Agregar un registro temporal para inspeccionar el payload
                $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                    ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                    : null;

                if (!$idGeneral) {
                    return response()->json([
                        'status' => 400,
                        'message' => 'No se pudo obtener el idGeneral del token',
                    ], 400);
                }

                //Validar que solo sea secretario
                $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

                $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
                    return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
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


    public function listarRequerimientosAbogado(Request $request)
    {
        try {

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');

            // Agregar un registro temporal para inspeccionar el payload
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            //Perfil abogado
            $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
            });

            if (!$tienePerfilAbogado) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos.',
                ], 403);
            }

            $requerimiento = Requerimiento::with([
                'historial:idHistorialEstadoRequerimientos,idCatEstadoRequerimientos,idRequerimiento'
            ])->where('idAbogado', $idGeneral)->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de requerimientos",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de requeirimientos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
