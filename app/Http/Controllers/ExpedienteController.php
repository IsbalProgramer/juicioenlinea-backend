<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Expediente;
use App\Models\PreRegistro;
use App\Services\PermisosApiService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpedienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        // Obtener el payload del token
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

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

        // Determinar si el usuario es abogado o secretario
        $esAbogado = collect($perfiles)->contains(function ($perfil) {
            return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
        });

        $esSecretario = collect($perfiles)->contains(function ($perfil) {
            return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
        });

        // Si no tiene ninguno de los perfiles, rechazar
        if (!$esAbogado && !$esSecretario) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        // Consulta condicional
        $expedientes = Expediente::with(['preRegistro.catMateriaVia.catMateria', 'preRegistro.catMateriaVia.catVia'])
            ->when($esAbogado, function ($query) use ($idGeneral) {
                $query->whereHas('preRegistro', function ($subquery) use ($idGeneral) {
                    $subquery->where('idGeneral', $idGeneral);
                });
            })
            ->when($esSecretario, function ($query) use ($idGeneral) {
                $query->orWhere('idSecretario', $idGeneral);
            })
            ->get();

        // Transformar la colección
        $expedientes->transform(function ($expediente) {
            $preRegistro = $expediente->preRegistro;
            return [
                'idExpediente'       => $expediente->idExpediente,
                'NumExpediente'      => $expediente->NumExpediente,
                'idCatJuzgado'       => $expediente->idCatJuzgado,
                'fechaResponse'      => $expediente->fechaResponse,
                'idPreregistro'      => $expediente->idPreregistro,
                'folioPreregistro'   => $preRegistro?->folioPreregistro,
                'idCatMateriaVia'    => $preRegistro?->idCatMateriaVia,
                'fechaCreada'        => $preRegistro?->fechaCreada,
                'created_at_pre'     => $preRegistro?->created_at,
                'materiaDescripcion' => optional($preRegistro?->catMateriaVia?->catMateria)->descripcion,
                'viaDescripcion'     => optional($preRegistro?->catMateriaVia?->catVia)->descripcion,
            ];
        });

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $expedientes
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idPreregistro' => 'required|integer|exists:pre_registros,idPreregistro',
            'NumExpediente' => 'required|string|max:255',
            'idCatJuzgado' => 'required|integer',
            'fechaResponse' => 'required|date',
            'idSecretario' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        // Verifica si el idPreregistro ya fue asignado a un expediente
        if (Expediente::where('idPreregistro', $request->idPreregistro)->exists()) {
            return response()->json([
                'success' => false,
                'status' => 409,
                'message' => 'El idPreregistro ya ha sido asignado a un expediente.',
            ], 409);
        }

        try {
            $expediente = Expediente::create([
                'idPreregistro' => $request->idPreregistro,
                'NumExpediente' => $request->NumExpediente,
                'idCatJuzgado' => $request->idCatJuzgado,
                'fechaResponse' => $request->fechaResponse,
                'idSecretario' => $request->idSecretario // asignacion del secretario
            ]);

            return response()->json([
                'success' => true,
                'status' => 201,
                'message' => 'Expediente creado correctamente',
                'data' => $expediente
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear el expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($idExpediente)
    {
        try {
            // Buscar el expediente con sus relaciones
            $expediente = Expediente::with([
                'preRegistro.historialEstado',
                'preRegistro.historialEstado.estado',
                'requerimientos.documentoAcuerdo',
                'requerimientos.historial',
                'requerimientos.historial.catEstadoRequerimiento',
                'tramites',
            ])->findOrFail($idExpediente);

            // Buscar todos los preregistros que tengan el mismo idPreregistro que el expediente encontrado
            $preregistros = PreRegistro::where('idPreregistro', $expediente->idPreregistro)->get();

            return response()->json([
                'success' => true,
                'status' => 200,
                'datos' => $expediente,
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'mensaje' => 'Expediente no encontrado',
            ], 404);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expediente $expediente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expediente $expediente)
    {
        //
    }

    public function listarAbogadosPorExpediente($idExpediente, Request $request)
    {
        try {
            // Buscar el expediente y su preregistro
            $expediente = Expediente::findOrFail($idExpediente);

            if (!$expediente) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el expediente con ID $idExpediente"
                ], 404);
            }

            $idPreregistro = $expediente->idPreregistro;

            // Obtener el preregistro relacionado
            $preregistro = PreRegistro::where('idPreregistro', $idPreregistro)->first();

            if (!$preregistro) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el preregistro para el expediente $idExpediente",
                ], 404);
            }

            // Obtener el idGeneral del preregistro (asumiendo que representa al abogado)
            $idGeneral = $preregistro->idGeneral ?? null;
            $usr = $preregistro->usr ?? null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el idGeneral del abogado para el expediente $idExpediente",
                ], 404);
            }
            if (!$usr) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el usuario del abogado para el expediente $idExpediente",
                ], 404);
            }

            // Consultar datos del usuario abogado en la API externa
            $apiDatos = 'https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/DatosUsuario';
            $response = Http::withToken($request->bearerToken())
                ->timeout(60)
                ->post("$apiDatos?usuario=" . $usr); // Cambia 'Usuario' por 'usuario'

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al consultar los datos del usuario abogado.',
                    'error' => $response->body(),
                ], 500);
            }

            // Ajusta el mapeo según la estructura real de la respuesta de la API

            $token = $request->bearerToken();
            $nombre = AuthHelper::obtenerNombreUsuarioDesdeApi($usr, $token);

            $abogado = [
                'idAbogado' => $idGeneral,
                'nombre' => $nombre,
            ];

            return response()->json([
                'status' => 200,
                'message' => "Listado de abogados para el expediente $idExpediente",
                'data' => [$abogado]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de abogados por expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Api para contar el numero de espedientes que se listan por usuario 
    public function contarExpedientesUsuario(Request $request, PermisosApiService $permisosApiService)
    {
        // Obtener el payload del token
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

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

        // Determinar si el usuario es abogado o secretario
        $esAbogado = collect($perfiles)->contains(function ($perfil) {
            return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
        });

        $esSecretario = collect($perfiles)->contains(function ($perfil) {
            return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
        });

        // Si no tiene ninguno de los perfiles, rechazar
        if (!$esAbogado && !$esSecretario) {
            return response()->json([
                'success' => false,
                'status' => 403,
                'message' => 'No tiene permisos para realizar esta acción.',
            ], 403);
        }

        // Contar expedientes dependiendo del rol
        $totalExpedientes = Expediente::when($esAbogado, function ($query) use ($idGeneral) {
            $query->whereHas('preRegistro', function ($subquery) use ($idGeneral) {
                $subquery->where('idGeneral', $idGeneral);
            });
        })
            ->when($esSecretario, function ($query) use ($idGeneral) {
                $query->orWhere('idSecretario', $idGeneral);
            })
            ->count();

        return response()->json([
            'success' => true,
            'status' => 200,
            'totalExpedientes' => $totalExpedientes
        ], 200);
    }

    
}
