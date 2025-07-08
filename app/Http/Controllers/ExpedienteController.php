<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Abogado;
use App\Models\Expediente;
use App\Models\ExpedienteAbogado;
use App\Models\PreRegistro;
use App\Services\PermisosApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

        $timezone = config('app.timezone', 'America/Mexico_City');
        if ($fechaInicioParam && $fechaFinalParam) {
            $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
        } elseif ($fechaInicioParam) {
            $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaInicioParam, $timezone)->endOfDay();
        } elseif ($fechaFinalParam) {
            $fechaInicio = Carbon::parse($fechaFinalParam, $timezone)->startOfDay();
            $fechaFinal = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
        } elseif (!$expediente) {
            $fechaInicio = Carbon::now()->subDays(6)->startOfDay();
            $fechaFinal = Carbon::now()->endOfDay();
        }

        // Base Query
        $query = Expediente::with(['preRegistro.catMateriaVia.catMateria', 'preRegistro.catMateriaVia.catVia', 'tramites'])
            ->when($esAbogado, function ($query) use ($idGeneral) {
                $query->where(function ($q) use ($idGeneral) {
                    $q->whereHas('preRegistro', function ($subquery) use ($idGeneral) {
                        $subquery->where('idGeneral', $idGeneral);
                    })
                        ->orWhereHas('tramites', function ($subquery) use ($idGeneral) {
                            $subquery->where('idGeneral', $idGeneral)
                                ->where('notificado', 1);
                        });
                });
            })
            ->when($esSecretario, function ($query) use ($idGeneral) {
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
            });

        // Traer todos
        $todos = $query->get();

        // Transformar
        $transformados = $todos->map(function ($expediente) {
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

        // Paginación manual
        $perPage = (int)$request->query('per_page', 10);
        $page = (int)$request->query('page', 1);
        $total = $transformados->count();
        $items = $transformados->forPage($page, $perPage)->values();

        $paginator = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => url()->current()]
        );

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ]
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

            // Relacionar al abogado con el expediente
            $preregistro = PreRegistro::find($request->idPreregistro);
            if ($preregistro && $preregistro->idGeneral) {
                $abogado = Abogado::where('idGeneral', $preregistro->idGeneral)->first();
                if ($abogado) {
                    ExpedienteAbogado::firstOrCreate([
                        'idExpediente' => $expediente->idExpediente,
                        'idAbogado' => $abogado->idAbogado,
                    ]);
                }
            }

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
            // Filtros
            $fechaInicioParam = $request->input('fechaInicio');
            $fechaFinalParam = $request->input('fechaFinal');
            $folio = $request->input('folio');
            $perPage = (int) $request->input('per_page', 5);
            $page = (int) $request->input('page', 1);
            $timezone = config('app.timezone', 'America/Mexico_City');
            $tipo = $request->input('tipo'); // <-- Nuevo filtro por tipo

            // Fechas
            if ($fechaInicioParam && $fechaFinalParam) {
                $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
                $fechaFin = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
            } elseif ($fechaInicioParam) {
                $fechaInicio = Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
                $fechaFin = Carbon::parse($fechaInicioParam, $timezone)->endOfDay();
            } elseif ($fechaFinalParam) {
                $fechaInicio = Carbon::parse($fechaFinalParam, $timezone)->startOfDay();
                $fechaFin = Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
            } else {
                $fechaInicio = Carbon::now($timezone)->subDays(7)->startOfDay();
                $fechaFin = Carbon::now($timezone)->endOfDay();
            }

            // Obtener expediente
            $expediente = Expediente::with([
                'juzgado',
                'preRegistro.partes',
                'preRegistro.partes.catTipoParte',
                'preRegistro.catMateriaVia.catMateria',
                'preRegistro.catMateriaVia.catVia',
            ])->findOrFail($idExpediente);

            // PreRegistro
            $preRegistro = $expediente->preRegistro()
                ->when($folio, fn($q) => $q->where('folioPreregistro', 'like', "%$folio%"))
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->with([
                    'historialEstado' => fn($q) => $q->orderByDesc('created_at')->limit(1),
                    'historialEstado.estado',
                    'partes.catTipoParte',
                    'catMateriaVia.catMateria',
                    'catMateriaVia.catVia'
                ])
                ->get()
                ->map(function ($item) {
                    $item->tipo = 'pre_registro';
                    return $item;
                });

            // Requerimientos
            $requerimientos = $expediente->requerimientos()
                ->with(['documentoAcuerdo', 'historial.catEstadoRequerimiento'])
                ->whereHas('documentoAcuerdo', fn($q) => $folio ? $q->where('folio', 'like', "%$folio%") : null)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('historial', fn($q) => $q->whereIn('idCatEstadoRequerimientos', [2, 4, 5]))
                ->get()
                ->map(function ($item) {
                    $item->tipo = 'requerimiento';
                    return $item;
                });

            // Trámites
            $tramites = $expediente->tramites()
                ->with(['historial.catEstadoTramite', 'catTramite'])
                ->when($folio, fn($q) => $q->where('folioOficio', 'like', "%$folio%"))
                ->where('notificado', 1)
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('historial', fn($q) => $q->whereIn('idCatEstadoTramite', [2]))
                ->get()
                ->map(function ($item) {
                    $item->tipo = 'tramite';
                    return $item;
                });

            // Audiencias
            $audiencias = $expediente->audiencias()
                ->with(['ultimoEstado.catalogoEstadoAudiencia', 'invitados'])
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->whereHas('ultimoEstado', function ($q) {
                    $q->whereIn('idCatalogoEstadoAudiencia', [2, 4]);
                })
                ->get()
                ->map(function ($audiencia) {
                    $audiencia->tipo = 'audiencia';
                    return $audiencia;
                });

            // Combinar todos
            $todo = collect()
                ->merge($preRegistro)
                ->merge($requerimientos)
                ->merge($tramites)
                ->merge($audiencias)
                ->sortByDesc('created_at')
                ->values();

            // Filtro por tipo (1=requerimiento, 2=tramite, 3=audiencia, 0 o null = todos)
            $tipo = $request->input('tipo');
            if ($tipo == 1) {
                $todo = $todo->where('tipo', 'requerimiento')->values();
            } elseif ($tipo == 2) {
                // Incluir trámites y preregistros
                $todo = $todo->whereIn('tipo', ['tramite', 'pre_registro'])->values();
            } elseif ($tipo == 3) {
                $todo = $todo->where('tipo', 'audiencia')->values();
            }
            // Si $tipo es 0 o null, no se filtra y se muestran todos los tipos (por defecto últimos 7 días)

            // Paginación
            $total = $todo->count();
            $items = $todo->forPage($page, $perPage)->values();

            $paginator = new LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => url()->current()]
            );

            return response()->json([
                'success' => true,
                'status' => 200,
                'data' => [
                    'expediente' => $expediente,
                    'registros' => $paginator->items(),
                    'pagination' => [
                        'current_page' => $paginator->currentPage(),
                        'per_page' => $paginator->perPage(),
                        'total' => $paginator->total(),
                        'last_page' => $paginator->lastPage(),
                    ]
                ]
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'Expediente no encontrado',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener los datos del expediente',
                'error' => $e->getMessage(),
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

    public function listarAbogadosExpediente($idExpediente)
    {
        try {
            // Buscar el expediente (lanza excepción si no existe)
            $expediente = Expediente::findOrFail($idExpediente);

            // Obtener los abogados vinculados al expediente desde la tabla pivot
            $abogados = DB::table('expediente_abogado as ea')
                ->join('abogados as a', 'ea.idAbogado', '=', 'a.idAbogado')
                ->where('ea.idExpediente', $idExpediente)
                ->select(
                    'ea.idExpedienteAbogado',
                    'ea.idExpediente',
                    'ea.idAbogado',
                    'a.nombre',
                    'a.correo',
                    'a.correoAlterno'
                )
                ->get();

            if (!$expediente) {
                return response()->json([
                    'status' => 400,
                    'message' => 'Expediente no encontrado',
                ], 400);
            }

            return response()->json([
                'status' => 200,
                'message' => "Lista de abogados vinculados al expediente $idExpediente",
                'data' => $abogados
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de abogado_expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function busquedaExpedienteDetalles(Request $request, PermisosApiService $permisosApiService)
    {
        $validator = Validator::make($request->all(), [
            'expediente' => 'required|string',
            'juzgado' => 'required|integer',
            'folio' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Obtener datos del usuario autenticado
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

        if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
            return response()->json([
                'message' => 'No se pudo identificar al usuario.'
            ], 401);
        }

        $idGeneral = $datosUsuario['idGeneral'];

        // Obtener ID del abogado desde la tabla abogado
        $idAbogado = DB::table('abogados')
            ->where('idGeneral', $idGeneral)
            ->value('idAbogado');

        if (!$idAbogado) {
            return response()->json([
                'message' => 'El usuario autenticado no está registrado como abogado.'
            ], 403);
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

        // Buscar el expediente
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

        // Verificar si ya está asignado a ese abogado
        $yaAsignado = DB::table('expediente_abogado')
            ->where('idExpediente', $expediente->idExpediente)
            ->where('idAbogado', $idAbogado)
            ->exists();

        if ($yaAsignado) {
            return response()->json([
                'status' => 409,
                'message' => '⚠️ Este expediente ya está asignado al abogado. No es necesario volver a consultarlo.',
            ], 409);
        }

        // Si todo bien, devolver el expediente
        return response()->json([
            'message' => 'Expediente encontrado correctamente.',
            'data' => $expediente
        ]);
    }

    public function relacionarAbogadoConExpediente($idExpediente)
    {
        $expediente = Expediente::find($idExpediente);
        if (!$expediente) {
            return response()->json([
                'success' => false,
                'message' => 'Expediente no encontrado',
            ], 404);
        }

        // Obtener el preregistro relacionado
        $preregistro = PreRegistro::find($expediente->idPreregistro);
        if (!$preregistro) {
            return response()->json([
                'success' => false,
                'message' => 'PreRegistro no encontrado para este expediente',
            ], 404);
        }

        // Buscar el abogado por idGeneral
        $abogado = Abogado::where('idGeneral', $preregistro->idGeneral)->first();
        if (!$abogado) {
            return response()->json([
                'success' => false,
                'message' => 'Abogado no encontrado con ese idGeneral',
            ], 404);
        }

        // Verificar si ya existe la relación
        $yaRelacionado = $expediente->abogados()->where('abogados.idAbogado', $abogado->idAbogado)->exists();
        if ($yaRelacionado) {
            return response()->json([
                'success' => true,
                'status' => 409,
                'message' => 'La relación ya existe',
            ]);
        }

        // Relacionar abogado con expediente
        $expediente->abogados()->attach($abogado->idAbogado);

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Abogado relacionado correctamente con el expediente',
        ]);
    }
}
