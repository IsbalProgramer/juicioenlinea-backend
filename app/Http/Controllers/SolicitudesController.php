<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Documento;
use App\Models\Solicitudes;
use App\Services\MailerSendService;
use App\Services\NasApiService;
use App\Services\PermisosApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SolicitudesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {
            // Obtener datos del usuario desde el token
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];

            // Obtener el sistema y perfiles
            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $idGeneral, 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario',
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

            $esAbogado = collect($perfiles)->contains(
                fn($perfil) =>
                isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado'
            );

            $esSecretario = collect($perfiles)->contains(
                fn($perfil) =>
                isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario'
            );

            if (!$esAbogado && !$esSecretario) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }

            // Filtros
            $expediente = $request->input('expediente'); // NumExpediente
            $estado = $request->input('estado'); // idCatalogoEstadoSolicitud
            $fechaInicio = $request->input('fechaInicio');
            $fechaFin = $request->input('fechaFinal');

            $solicitudesQuery = Solicitudes::with(['primerEstado', 'ultimoEstado.estado', 'audiencia.expediente']);

            // Filtro por usuario
            if ($esAbogado && !$esSecretario) {
                $solicitudesQuery->where('idGeneral', $idGeneral);
            } else {
                $solicitudesQuery->whereHas('audiencia.expediente', function ($q) use ($idGeneral) {
                    $q->where('idSecretario', $idGeneral);
                });
            }

            // Filtro por expediente (NumExpediente)
            if ($expediente) {
                $solicitudesQuery->whereHas('audiencia.expediente', function ($q) use ($expediente) {
                    $q->where('NumExpediente', 'like', "%$expediente%");
                });
            }

            // Filtro por estado (último estado)
            if (!is_null($estado) && $estado != 0) {
                $solicitudesQuery->whereHas('ultimoEstado', function ($q) use ($estado) {
                    $q->where('idCatalogoEstadoSolicitud', $estado);
                });
            }

            // Filtro por fecha de creación de la solicitud
            if ($fechaInicio && $fechaFin) {
                $solicitudesQuery->whereBetween('created_at', [
                    Carbon::parse($fechaInicio)->startOfDay(),
                    Carbon::parse($fechaFin)->endOfDay()
                ]);
            } elseif ($fechaInicio) {
                $solicitudesQuery->whereDate('created_at', '>=', Carbon::parse($fechaInicio)->startOfDay());
            } elseif ($fechaFin) {
                $solicitudesQuery->whereDate('created_at', '<=', Carbon::parse($fechaFin)->endOfDay());
            }

            // Paginación
            $perPage = (int)$request->query('per_page', 10);
            $page = (int)$request->query('page', 1);

            $paginator = $solicitudesQuery->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Listado de solicitudes',
                'data' => $paginator->items(),
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener las solicitudes',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, NasApiService $nasApiService, PermisosApiService $permisosApiService)
    {
        try {
            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

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
                    'message' => 'No se pudo obtener el idGeneral',
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'idAudiencia'   => 'required|integer',
                'observaciones' => 'nullable|string',
                'documento'     => 'required|file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $validated = $validator->validated();

            // Verificar que exista la audiencia
            $audiencia = Audiencia::with('expediente.abogados')->where('idAudiencia', $validated['idAudiencia'])->first();
            if (!$audiencia) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'La audiencia especificada no existe.',
                    'data' => null,
                ], 404);
            }


            // Validar que el abogado esté relacionado al expediente
            $abogadoRelacionado = $audiencia->expediente->abogados->contains(function ($abogado) use ($idGeneral) {
                return $abogado->idGeneral == $idGeneral;
            });
            if (!$abogadoRelacionado) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tienes los permisos necesarios para realizar esta acción.',
                ], 403);
            }


            // Verificar si ya existe una solicitud pendiente (último estado = 1) para ese usuario y audiencia
            $solicitudPendiente = Solicitudes::where('idAudiencia', $validated['idAudiencia'])
                ->where('idGeneral', $idGeneral)
                ->whereHas('ultimoEstado', function ($q) {
                    $q->where('idCatalogoEstadoSolicitud', 1);
                })
                ->first();

            if ($solicitudPendiente) {
                return response()->json([
                    'success' => false,
                    'status' => 409,
                    'message' => 'Ya existe una solicitud para esta audiencia.',
                    'data' => null,
                ], 409);
            }
            
            $solicitudAceptada = Solicitudes::where('idAudiencia', $validated['idAudiencia'])
                ->where('idGeneral', $idGeneral)
                ->whereHas('ultimoEstado', function ($q) {
                    $q->where('idCatalogoEstadoSolicitud', 2); // 2 = aceptada
                })
                ->first();

            if ($solicitudAceptada) {
                return response()->json([
                    'success' => false,
                    'status' => 409,
                    'message' => 'Ya existe una solicitud aceptada para esta audiencia.',
                    'data' => null,
                ], 409);
            }
            // Subir el documento al NAS
            $file = $request->file('documento');
            $anio = now()->year;
            $timestamp = now()->format('Y_m_d_His');
            $nombreDocumento = $file->getClientOriginalName();
            $nombreArchivo = "{$timestamp}_-1_{$nombreDocumento}";
            $ruta = "SitiosWeb/JuicioLinea/SOLICITUDES/{$anio}";

            // Subir archivo al NAS
            $nasApiService->subirArchivo($file, $ruta, $request->bearerToken(), $nombreArchivo);

            // Guardar el documento en la base de datos
            $documento = Documento::create([
                'idCatTipoDocumento' => -1,
                'nombre' => $nombreDocumento,
                'documento' => $ruta . '/' . $nombreArchivo,
            ]);
            // Crear el folio consecutivo para la solicitud
            // Obtener la audiencia y expediente relacionados
            $audiencia = Audiencia::with('expediente')->where('idAudiencia', $validated['idAudiencia'])->first();
            $folioAudiencia = $audiencia->folio;
            $folioExpediente = $audiencia->expediente->NumExpediente;


            // Crear el folio consecutivo para la solicitud (único por año)
            $anio = now()->year;
            $ultimoFolio = Solicitudes::where('folio', 'like', 'SOL-%/' . $anio . '%')
                ->orderByDesc('idSolicitud')
                ->value('folio');

            if ($ultimoFolio) {
                preg_match('/SOL-(\d{4})\/' . $anio . '/', $ultimoFolio, $matches);
                $numeroConsecutivo = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
            } else {
                $numeroConsecutivo = 1;
            }

            $folioSolicitud = 'SOL-' . str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio
                . '-EXP-' . $folioExpediente
                . '-AUD-' . $folioAudiencia;
            // Crear la solicitud
            $solicitud = Solicitudes::create([
                'idAudiencia'   => $validated['idAudiencia'],
                'idGeneral'     => $idGeneral,
                'observaciones' => $validated['observaciones'] ?? null,
                'folio'         => $folioSolicitud,
            ]);

            // Insertar historial de estado de la solicitud (pendiente) with el documento
            $solicitud->historialEstado()->create([
                'idCatalogoEstadoSolicitud' => 1,
                'fechaEstado' => now(),
                'observaciones' => 'SOLICITUD ENVIADA.',
                'idDocumento' => $documento->idDocumento,
            ]);
            $mailerSend = new MailerSendService();
            $mailerSend->enviarCorreo(
                "dvirdr2@gmail.com", // destinatario, puedes hacerlo dinámico
                "Solicitud de grabación #{$solicitud->folio}",
                [
                    'mensaje' => "solicitud creada exitosamente",
                    'descripcion' => "Se enviará un correo una vez que la solicutd haya sido atentida",
                    "folio" => $solicitud->folio,
                    "fecha" => Carbon::parse($solicitud->ultimoEstado->fechaEstado)->format('d/m/Y H:i') . 'h',
                    "via" => $solicitud->audiencia->expediente->NumExpediente,
                    "sistesis" => $solicitud->audiencia->folio,
                    "observaciones" => $solicitud->observaciones,

                    "Nfolio" => "Folio de audiencia",
                    "Nfecha" => "Fecha de creación",
                    "Nvia" => "Expediente",
                    "Nsitesis" => "Audiencia",
                    "Nobservaciones" => "Observaciones",
                    "correo" => "dvirdr2@gmail.com"
                ],
                "neqvygm997wl0p7w" // tu template_id
            );
            return response()->json([
                'success' => true,
                'status' => 201,
                'message' => 'Solicitud creada correctamente.',
                'data' => $solicitud,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear la solicitud.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id, Request $request, PermisosApiService $permisosApiService)
    {
        try {
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];

            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $idGeneral, 4171);
            if (!$idSistema) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario',
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

            $esAbogado = collect($perfiles)->contains(
                fn($perfil) =>
                isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado'
            );

            $esSecretario = collect($perfiles)->contains(
                fn($perfil) =>
                isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario'
            );

            if (!$esAbogado && !$esSecretario) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }

            // Buscar la solicitud sin cargar grabaciones todavía
            $solicitud = Solicitudes::with([
                'grabacion.audiencia.expediente'
            ])->find($id);

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Solicitud no encontrada',
                ], 404);
            }

            // Validar pertenencia
            $puedeVer = false;
            if ($esAbogado && !$esSecretario) {
                $puedeVer = $solicitud->idGeneral == $idGeneral;
            } else {
                $puedeVer = optional($solicitud->grabacion->audiencia->expediente)->idSecretario == $idGeneral;
            }

            if (!$puedeVer) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tiene permisos para ver esta solicitud.',
                ], 403);
            }

            // Si quieres paginar grabaciones:
            $perPage = (int)$request->query('per_page', 2);
            $page = (int)$request->query('page', 1);

            $grabaciones = $solicitud->grabacion()->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Solicitud encontrada',
                'data' => [
                    'solicitud' => $solicitud,
                    'grabaciones' => $grabaciones->items(),
                    'pagination' => [
                        'current_page' => $grabaciones->currentPage(),
                        'per_page' => $grabaciones->perPage(),
                        'total' => $grabaciones->total(),
                        'last_page' => $grabaciones->lastPage(),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener la solicitud',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $idSolicitud, NasApiService $nasApiService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:2,3',
                'observaciones' => 'required|string',
                'documento' => 'nullable|file',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status' => 422,
                    'message' => 'Error de validación',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Buscar la solicitud por su ID
            $solicitud = Solicitudes::with('ultimoEstado')->find($idSolicitud);
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'Solicitud no encontrada',
                ], 404);
            }

            // Obtener el último estado usando la relación
            $ultimoEstado = $solicitud->ultimoEstado;

            if ($ultimoEstado && $ultimoEstado->idCatalogoEstadoSolicitud == 3) {
                return response()->json([
                    'success' => false,
                    'status' => 409,
                    'message' => 'No se puede actualizar una solicitud que ya fue rechazada.',
                ], 409);
            }

            $estado = (int) $request->input('estado');


            $idDocumento = null;

            // Si se sube un documento, guárdalo en el NAS y en la base de datos
            if ($request->hasFile('documento')) {
                $file = $request->file('documento');
                $anio = now()->year;
                $timestamp = now()->format('Y_m_d_His');
                $nombreDocumento = $file->getClientOriginalName();
                $nombreArchivo = "{$timestamp}_-1_{$nombreDocumento}";
                $ruta = "SitiosWeb/JuicioLinea/SOLICITUDES/{$anio}";

                // Subir archivo al NAS
                $nasApiService->subirArchivo($file, $ruta, $request->bearerToken(), $nombreArchivo);

                // Guardar el documento en la base de datos
                $documento = Documento::create([
                    'idCatTipoDocumento' => -1,
                    'nombre' => $nombreDocumento,
                    'documento' => $ruta . '/' . $nombreArchivo,
                ]);
                $idDocumento = $documento->idDocumento;
            }

            // Crear nuevo historial de estado
            $historial = $solicitud->historialEstado()->create([
                'idCatalogoEstadoSolicitud' => $estado,
                'fechaEstado' => now(),
                'observaciones' => $request->input('observaciones'),
                'idDocumento' => $idDocumento,
            ]);


            $mailerSend = new MailerSendService();
            $mailerSend->enviarCorreo(
                "dvirdr2@gmail.com", // destinatario, puedes hacerlo dinámico
                "Actualizacion a la solicitud #{$solicitud->folio}",
                [
                    'mensaje' => "respuesta a la solicitud",
                    'descripcion' => "Ingrese a la plataforma de juicio en línea para ver el estado de la solicitud",
                    "folio" => $solicitud->folio,
                    "fecha" => Carbon::parse($historial->fechaEstado)->format('d/m/Y H:i') . 'h',
                    "via" => $solicitud->audiencia->expediente->NumExpediente,
                    "sistesis" => $solicitud->audiencia->folio,
                    "observaciones" => $historial->observaciones,

                    "Nfolio" => "Folio de audiencia",
                    "Nfecha" => "Fecha de creación",
                    "Nvia" => "Expediente",
                    "Nsitesis" => "Audiencia",
                    "Nobservaciones" => "Observaciones",
                    "correo" => "dvirdr2@gmail.com"
                ],
                "neqvygm997wl0p7w" // tu template_id
            );
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Solicitud actualizada correctamente.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al actualizar la solicitud.',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
