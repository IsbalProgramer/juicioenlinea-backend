<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Expediente;
use App\Models\PreRegistro;
use App\Services\PermisosApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        // Filtros
        $expediente = $request->query('expediente');
        $fechaInicioParam = $request->query('fechaInicio');
        $fechaFinalParam = $request->query('fechaFinal');

        $fechaInicio = null;
        $fechaFinal = null;

        // Aplicar filtro de fechas si se mandan
        $timezone = config('app.timezone', 'America/Mexico_City');
        if ($fechaInicioParam && $fechaFinalParam) {
            // Si ambas fechas, usar startOfDay para inicio y endOfDay para final (inclusivo)
            $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
        } elseif ($fechaInicioParam) {
            // Solo fecha de inicio, filtra solo ese día completo
            $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaInicioParam, $timezone)->endOfDay();
        } elseif ($fechaFinalParam) {
            // Solo fecha final, filtra solo ese día completo
            $fechaInicio = Carbon::parse($fechaFinalParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
        } elseif (!$expediente) {
            // Solo si no hay expediente ni fechas, usar los últimos 7 días por defecto
            $fechaInicio = Carbon::now()->subDays(6)->startOfDay();
            $fechaFinal = Carbon::now()->endOfDay();
        }

        // Consulta

        $expedientes = Expediente::with(['preRegistro.catMateriaVia.catMateria', 'preRegistro.catMateriaVia.catVia', 'tramites'])
            ->when($esAbogado, function ($query) use ($idGeneral) {
            $query->where(function ($q) use ($idGeneral) {
                // Expedientes donde el preregistro es suyo
                $q->whereHas('preRegistro', function ($subquery) use ($idGeneral) {
                $subquery->where('idGeneral', $idGeneral);
                })
                // O expedientes donde haya hecho algún trámite (aunque no sea el preregistro)
                ->orWhereHas('tramites', function ($subquery) use ($idGeneral) {
                $subquery->where('idGeneral', $idGeneral);
                });
            });
            })
            ->when($esSecretario, function ($query) use ($idGeneral) {
            // Solo los que es secretario O donde haya hecho un trámite (prueba)
            $query->orWhere(function ($q) use ($idGeneral) {
                $q->where('idSecretario', $idGeneral)
                  ->orWhereHas('tramites', function ($subquery) use ($idGeneral) {
                  $subquery->where('idGeneral', $idGeneral);
                  });
            });
            })
            ->when($expediente, function ($query) use ($expediente) {
            $query->where('numExpediente', 'like', "%{$expediente}%");
            })
            ->when($fechaInicio && $fechaFinal, function ($query) use ($fechaInicio, $fechaFinal) {
            $query->whereBetween('fechaResponse', [$fechaInicio, $fechaFinal]);
            })
            ->get();

        // Transformar
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

    public function show(Request $request, $idExpediente)
    {
        try {
            $fechaInicio = $request->input('fechaInicio');
            $fechaFin = $request->input('fechaFinal');
            $tipoFiltro = $request->input('tipo'); // 0: todos, 1: requerimientos, 2: trámites, 3: grabaciones, 4: otros
            $folio = $request->input('folio');

            // Por defecto: últimos 7 días solo si no se proporcionan fechas
            if (!$fechaInicio || !$fechaFin) {
                $fechaFin = now();
                $fechaInicio = now()->subDays(7);
            }

            // Buscar expediente con relaciones
            $expediente = Expediente::with([
                // 'preRegistro',
                'juzgado'
            ])->findOrFail($idExpediente);

            // preregistro (filtrado directo por folio y fecha) con último estado del historialEstado
            $preRegistro = ($tipoFiltro === null || $tipoFiltro == 0 || $tipoFiltro == 2)
                ? $expediente->preRegistro()
                ->when($folio, function ($q) use ($folio) {
                    $q->where('folioPreregistro', 'like', '%' . $folio . '%');
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->with(['historialEstado' => function ($q) {
                    $q->orderByDesc('created_at')->limit(1);
                }, 'historialEstado.estado'])
                ->with(['partes.catTipoParte', 'catMateriaVia.catMateria', 'catMateriaVia.catVia'])
                ->get()
                : collect();

            // Requerimientos (filtrado por documentoAcuerdo.folio, fecha y estado final 2, 4 o 5)
            $requerimientos = ($tipoFiltro === null || $tipoFiltro == 0 || $tipoFiltro == 1)
                ? $expediente->requerimientos()
                ->with(['documentoAcuerdo', 'historial.catEstadoRequerimiento'])
                ->whereHas('documentoAcuerdo', function ($q) use ($folio) {
                    if ($folio) {
                        $q->where('folio', 'like', '%' . $folio . '%');
                    }
                })
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('historial', function ($q) {
                    $q->whereIn('idCatEstadoRequerimientos', [2, 4, 5]);
                })
                ->get()
                : collect();

            // Trámites (filtrado directo por folio y fecha)
            $tramites = ($tipoFiltro === null || $tipoFiltro == 0 || $tipoFiltro == 2)
                ? $expediente->tramites()
                ->with(['historial.catEstadoTramite']) // Asegúrate de traer la relación también
                ->with(['catTramite']) // Asegúrate de traer la relación también
                ->when($folio, function ($q) use ($folio) {
                    $q->where('folioOficio', 'like', '%' . $folio . '%');
                })
                ->where('notificado', 1)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('historial', function ($q) {
                    $q->whereIn('idCatEstadoTramite', [2]); // Aquí defines los estados válidos
                })
                ->get()
                : collect();


            return response()->json([
                'success' => true,
                'status' => 200,
                'datos' => [
                    'expediente' => $expediente,
                    'pre_registro' => $preRegistro,
                    'requerimientos' => $requerimientos,
                    'tramites' => $tramites,
                ],
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'mensaje' => 'Expediente no encontrado',
            ]);
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
            // Buscar el expediente
            $expediente = Expediente::findOrFail($idExpediente);

            // Obtener el idGeneral del preregistro (si existe)
            $idPreregistro = $expediente->idPreregistro;
            $preregistro = PreRegistro::where('idPreregistro', $idPreregistro)->first();
            $idGeneralPreregistro = $preregistro?->idGeneral;
            $usrPreregistro = $preregistro?->usr;

            // Obtener todos los idGeneral de trámites (distintos) para este expediente
            $tramitesAbogadosIds = $expediente->tramites()
                ->whereNotNull('idGeneral')
                ->pluck('idGeneral')
                ->unique()
                ->toArray();

            // Obtener los usuarios (usr) de los trámites
            $tramitesAbogadosUsr = $expediente->tramites()
                ->whereIn('idGeneral', $tramitesAbogadosIds)
                ->pluck('usr', 'idGeneral')
                ->toArray();

            $abogados = [];
            $token = $request->bearerToken();

            // Agregar abogado del preregistro si existe
            if ($idGeneralPreregistro && $usrPreregistro) {
                $nombre = AuthHelper::obtenerNombreUsuarioDesdeApi($usrPreregistro, $token);
                $abogados[$idGeneralPreregistro] = [
                    'idAbogado' => $idGeneralPreregistro,
                    'nombre' => $nombre,
                    'tipo' => 'preregistro'
                ];
            }

            // Agregar abogados de trámites (evitar duplicados)
            foreach ($tramitesAbogadosUsr as $idGeneral => $usr) {
                if ($usr && (!isset($abogados[$idGeneral]))) {
                    $nombre = AuthHelper::obtenerNombreUsuarioDesdeApi($usr, $token);
                    $abogados[$idGeneral] = [
                        'idAbogado' => $idGeneral,
                        'nombre' => $nombre,
                        'tipo' => 'tramite'
                    ];
                }
            }

            return response()->json([
                'status' => 200,
                'message' => "Listado de abogados (preregistro y trámites) para el expediente $idExpediente",
                'data' => array_values($abogados)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de abogados por expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Api para contar el numero de expedientes que se listan por usuario 
    public function contarExpedientesUsuario(Request $request, PermisosApiService $permisosApiService)
    {
        // Obtener el payload del token
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

        if ($totalExpedientes === 0) {
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Aún no existe ningún expediente para este usuario.',
                'totalExpedientes' => 0
            ], 200);
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'totalExpedientes' => $totalExpedientes
        ], 200);
    }

    // Obtener el detalle del expediente por el número de expediente en formato 0000/0000
    public function busquedaExpedienteDetalles(Request $request)
    {

        // Validar el request
        $validator = Validator::make($request->all(), [
            'expediente' => 'required|string',
            'juzgado' => 'required|integer',
            'folio' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Buscar el preregistro por folio
        $preregistro = DB::table('pre_registros')
            ->where('folioPreregistro', $request->folio)
            ->value('idPreregistro');

        if (!$preregistro) {
            return response()->json([
                'message' => 'No se encontró un preregistro con ese folio.'
            ], 404);
        }
        // Buscar el expediente que coincida con todos los datos
        $expediente = DB::table('expedientes')
            ->where('NumExpediente', $request->expediente)
            ->where('idCatJuzgado', $request->juzgado)
            ->where('idPreregistro', $preregistro)
            ->first();

        if (!$expediente) {
            return response()->json([
                'message' => 'No se encontró un expediente con los datos proporcionados.'
            ], 404);
        }



        return response()->json([
            'message' => 'Expediente encontrado correctamente.',
            'data' => $expediente
        ]);
    }
}
