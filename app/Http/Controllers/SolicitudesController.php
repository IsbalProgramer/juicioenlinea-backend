<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Documento;
use App\Models\Grabaciones;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Solicitudes;
use App\Services\PermisosApiService;
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

            // Si es abogado, solo sus solicitudes
            if ($esAbogado && !$esSecretario) {
                $solicitudes = Solicitudes::with(['grabacion.audiencia.expediente'])
                    ->where('idGeneral', $idGeneral)
                    ->get();
            } else {
                // Si es secretario, buscar solicitudes de expedientes donde es secretario
                $solicitudes = Solicitudes::with(['grabacion.audiencia.expediente'])
                    ->whereHas('grabacion.audiencia.expediente', function ($q) use ($idGeneral) {
                        $q->where('idSecretario', $idGeneral);
                    })
                    ->get();
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Listado de solicitudes',
                'data' => $solicitudes,
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
    public function store(Request $request, \App\Services\NasApiService $nasApiService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'idGrabacion'   => 'required|integer',
                'idGeneral'     => 'required|integer',
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

            // Verificar que exista la grabación
            $grabacionExiste = Grabaciones::where('idGrabacion', $validated['idGrabacion'])->exists();
            if (!$grabacionExiste) {
                return response()->json([
                    'success' => false,
                    'status' => 404,
                    'message' => 'La grabación especificada no existe.',
                    'data' => null,
                ], 404);
            }

            // Verificar si ya existe una solicitud pendiente (status 1 o 3) para ese usuario y grabación
            $solicitudPendiente = Solicitudes::where('idGrabacion', $validated['idGrabacion'])
                ->where('idGeneral', $validated['idGeneral'])
                ->whereHas('historialEstadoSolicitud', function ($q) {
                    $q->whereIn('idCatalogoEstadoSolicitud', [1, 3]);
                })
                ->first();

            if ($solicitudPendiente) {
                return response()->json([
                    'success' => false,
                    'status' => 409,
                    'message' => 'Ya existe una solicitud pendiente para esta grabación.',
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

            // Crear la solicitud
            $solicitud = Solicitudes::create([
                'idGrabacion'   => $validated['idGrabacion'],
                'idGeneral'     => $validated['idGeneral'],
                'observaciones' => $validated['observaciones'] ?? null,
            ]);

            // Insertar historial de estado de la solicitud (pendiente) con el documento
            $solicitud->historialEstado()->create([
                'idCatalogoEstadoSolicitud' => 1,
                'fechaEstado' => now(),
                'observaciones' => 'SOLICITUD CREADA Y EN REVISIÓN.',
                'idDocumento' => $documento->idDocumento,
            ]);

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

            // Buscar la solicitud con relaciones
            $solicitud = Solicitudes::with(['grabacion.audiencia.expediente'])
                ->find($id);

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
                // Solo si es suya
                $puedeVer = $solicitud->idGeneral == $idGeneral;
            } else {
                // Secretario: si el expediente le pertenece
                $puedeVer = optional($solicitud->grabacion->audiencia->expediente)->idSecretario == $idGeneral;
            }

            if (!$puedeVer) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tiene permisos para ver esta solicitud.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Solicitud encontrada',
                'data' => $solicitud,
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
    public function update(Request $request, string $idSolicitud, \App\Services\NasApiService $nasApiService)
    {
        try {
            $validator = Validator::make($request->all(), [
                'estado' => 'required|in:2,3',
                'observaciones' => 'required_if:estado,3|string|nullable',
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
            $observaciones = $estado === 2
                ? 'SOLICITUD ACEPTADA, YA PUEDE CONSULTAR LA GRABACION DE LA AUDIENICA'
                : $request->input('observaciones');

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
            $solicitud->historialEstado()->create([
                'idCatalogoEstadoSolicitud' => $estado,
                'fechaEstado' => now(),
                'observaciones' => $observaciones,
                'idDocumento' => $idDocumento,
            ]);

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
