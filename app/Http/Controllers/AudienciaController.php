<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Documento;
use App\Models\Solicitudes;
use Illuminate\Http\Request;
use App\Services\MeetingService;
use App\Services\NasApiService;
use App\Services\PermisosApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class AudienciaController extends Controller
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

            // Parámetros de filtro
            $fechaInicioParam = $request->query('fechaInicio');
            $fechaFinalParam = $request->query('fechaFinal');
            $folio = $request->query('folio');
            $estado = $request->query('estado');

            $fechaInicio = null;
            $fechaFinal = null;

            // Por defecto, solo mostrar audiencias del día de hoy
            $timezone = config('app.timezone', 'America/Mexico_City');
            if ($fechaInicioParam && $fechaFinalParam) {
                $fechaInicio = \Carbon\Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
                $fechaFinal = \Carbon\Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
            } elseif ($fechaInicioParam) {
                $fechaInicio = \Carbon\Carbon::parse($fechaInicioParam, $timezone)->startOfDay();
                $fechaFinal = \Carbon\Carbon::parse($fechaInicioParam, $timezone)->endOfDay();
            } elseif ($fechaFinalParam) {
                $fechaInicio = \Carbon\Carbon::parse($fechaFinalParam, $timezone)->startOfDay();
                $fechaFinal = \Carbon\Carbon::parse($fechaFinalParam, $timezone)->endOfDay();
            } else {
                // Si no hay filtro, solo hoy
                $fechaInicio = \Carbon\Carbon::now($timezone)->startOfDay();
                $fechaFinal = \Carbon\Carbon::now($timezone)->endOfDay();
            }

            // --- Filtro por perfil ---
            $audienciasQuery = Audiencia::with(['expediente', 'ultimoEstado.catalogoEstadoAudiencia'])
                // ->when($folio, function ($query) use ($folio) {
                //     $query->whereHas('expediente', function ($q) use ($folio) {
                //         $q->where('NumExpediente', 'like', "%{$folio}%");
                //     });
                // })
                //filtro por folio de la audiencia 
                ->when($folio, function ($query) use ($folio) {
                    $query->where('folio', 'like', "%{$folio}%");
                })
                ->when($fechaInicio && $fechaFinal, function ($query) use ($fechaInicio, $fechaFinal) {
                    $query->whereBetween('start', [$fechaInicio, $fechaFinal]);
                })
                ->when(!is_null($estado) && $estado != 0, function ($query) use ($estado) {
                    $query->whereHas('ultimoEstado', function ($q) use ($estado) {
                        if ($estado == 1) {
                            $q->whereIn('idCatalogoEstadoAudiencia', [1, 3]);
                        } elseif ($estado == 2) {
                            $q->where('idCatalogoEstadoAudiencia', 2);
                        } elseif ($estado == 3) {
                            $q->where('idCatalogoEstadoAudiencia', 4);
                        }
                    });
                });

            if ($esSecretario) {
                // Solo audiencias donde expediente.idSecretario = idGeneral
                $audienciasQuery->whereHas('expediente', function ($q) use ($idGeneral) {
                    $q->where('idSecretario', $idGeneral);
                });
            } elseif ($esAbogado) {
                // Solo audiencias donde expediente.abogados contiene idAbogado = idGeneral
                $audienciasQuery->whereHas('expediente.abogados', function ($q) use ($idGeneral) {
                    $q->where('abogados.idGeneral', $idGeneral);
                });
            }

            // Paginación manual
            $perPage = (int)$request->query('per_page', 10);
            $page = (int)$request->query('page', 1);

            $paginator = $audienciasQuery
                ->orderByDesc('created_at')
                ->paginate($perPage, ['*'], 'page', $page);

            // Actualiza el estado de las audiencias de la página actual si corresponde
            $ahora = now();
            foreach ($paginator->items() as $audiencia) {
                if (
                    $audiencia->ultimoEstado &&
                    in_array($audiencia->ultimoEstado->idCatalogoEstadoAudiencia, [1, 3])
                ) {
                    $fin = \Carbon\Carbon::parse($audiencia->end);
                    if ($ahora->gt($fin)) {
                        // Cambia el estado a 2 (finalizada)
                        $audiencia->historialEstados()->create([
                            'idCatalogoEstadoAudiencia' => 2,
                            'fechaHora' => $ahora,
                            'observaciones' => 'Audiencia finalizada automáticamente por el sistema.',
                        ]);
                        // Recarga la relación para reflejar el cambio en la respuesta
                        $audiencia->load('ultimoEstado.catalogoEstadoAudiencia');
                    }
                }
            }

            // Transformar los datos para la respuesta
            $data = collect($paginator->items())->map(function ($audiencia) {
                $arr = $audiencia->toArray();
                if ($audiencia->ultimoEstado) {
                    $arr['ultimo_estado'] = [
                        'idHistorialEstadoAudiencia'    => $audiencia->ultimoEstado->idHistorialEstadoAudiencia,
                        'idAudiencia'                   => $audiencia->ultimoEstado->idAudiencia,
                        'idCatalogoEstadoAudiencia'     => $audiencia->ultimoEstado->idCatalogoEstadoAudiencia,
                        'descripcion'                   => $audiencia->ultimoEstado->catalogoEstadoAudiencia->descripcion ?? null,
                        'fechaHora'                     => $audiencia->ultimoEstado->fechaHora,
                        'observaciones'                 => $audiencia->ultimoEstado->observaciones,
                    ];
                } else {
                    $arr['ultimo_estado'] = null;
                }
                unset($arr['ultimoEstado']);
                return $arr;
            });

            return response()->json([
                'status' => 200,
                'message' => 'Listado de audiencias',
                'data' => $data,
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'last_page' => $paginator->lastPage(),
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener las audiencias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, MeetingService $meetingService)
    {
        $validator = Validator::make($request->all(), [
            'idExpediente' => 'required|integer|exists:expedientes,idExpediente',
            'title' => 'required|string|max:255',
            'agenda' => 'nullable|string|max:255',
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end' => 'required|date_format:Y-m-d H:i:s|after:start',
            'invitees' => 'required|array|min:1',
            'invitees.*.correo' => 'required|email|max:255',
            'invitees.*.correoAlterno' => 'nullable|email|max:255',
            'invitees.*.nombre' => 'required|string|max:255',
            'invitees.*.direccion' => 'nullable|string|max:255',
            'invitees.*.esAbogado' => 'nullable|boolean',
            'invitees.*.idCatSexo' => 'nullable|integer',
            'invitees.*.idCatTipoParte' => 'nullable|integer',
            'invitees.*.idUsr' => 'nullable|string',
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

        // 1. Obtener datos del usuario logueado
        $token = $request->bearerToken();
        $idUsr = $request->user()->idUsr ?? null; // O ajusta según cómo obtienes el idUsr del usuario logueado

        $servicio = new PermisosApiService();
        $respuesta = $servicio->obtenerDatosUsuarioByApi($token, $idUsr);

        $usuarioLogueado = null;
        if (!empty($respuesta['data']['pD_Abogados'][0])) {
            $abogado = $respuesta['data']['pD_Abogados'][0];
            $usuarioLogueado = [
                'email' => $abogado['correo'] ?? null,
                'displayName' => $abogado['nombre'] ?? null,
            ];
            if (!empty($abogado['correoAlterno'])) {
                $usuarioLogueado['correoAlterno'] = $abogado['correoAlterno'];
            }
        }


        // 2. Normalizar los invitados del request
        $invitees = [];
        foreach ($validated['invitees'] as $invitado) {
            $invitees[] = [
                'email' => $invitado['correo'],
                'displayName' => $invitado['nombre'],
            ];
            if (!empty($invitado['correoAlterno'])) {
                $invitees[] = [
                    'email' => $invitado['correoAlterno'],
                    'displayName' => $invitado['nombre'],
                ];
            }
        }

        // 3. Agregar usuario logueado solo para Webex (no guardar en tabla)
        if ($usuarioLogueado && !empty($usuarioLogueado['email'])) {
            $invitees[] = [
                'email' => $usuarioLogueado['email'],
                'displayName' => $usuarioLogueado['displayName'],
            ];
            if (!empty($usuarioLogueado['correoAlterno'])) {
                $invitees[] = [
                    'email' => $usuarioLogueado['correoAlterno'],
                    'displayName' => $usuarioLogueado['displayName'],
                ];
            }
        }

        $validated['invitees'] = $invitees;
        Log::info('Datos validados para crear audiencia:', $validated);
        Log::info('Invitees enviados a Webex:', $validated['invitees']);


        try {
            $webexToken = env('WEBEX_TOKEN');

            // Solo pasa los datos validados, MeetingService se encarga de los defaults
            $webexResponse = $meetingService->crearReunion($webexToken, $validated);

            if (!isset($webexResponse['webLink'])) {
                return response()->json($webexResponse);
            }

            $webLink = $webexResponse['webLink'];
            $idMeeting = $webexResponse['id'] ?? null;
            $meetingNumber = $webexResponse['meetingNumber'] ?? null;
            $password = $webexResponse['password'] ?? null;
            // Crear el folio consecutivo para la solicitud
            $ultimoFolio = Audiencia::latest('idAudiencia')->value('folio');
            $numeroConsecutivo = $ultimoFolio ? intval(explode('/', $ultimoFolio)[0]) + 1 : 1;
            $anio = now()->year;
            $folio = str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio;

            $audiencia = Audiencia::create([
                'folio' => $folio,
                'idExpediente' => $validated['idExpediente'],
                'title' => $validated['title'],
                'agenda' => $validated['agenda'] ?? null,
                'start' => $validated['start'],
                'end' => $validated['end'],
                'webLink' => $webLink,
                'hostEmail' => 'unidad.informatica.dpi@gmail.com', // <-- aquí el valor fijo
                'id' => $idMeeting,
                'meetingNumber' => $meetingNumber,
                'password' => $password,
            ]);

            foreach ($request->input('invitees') as $invitado) {
                $audiencia->invitados()->create([
                    'correo' => $invitado['correo'],
                    'correoAlterno' => $invitado['correoAlterno'] ?? null,
                    'nombre' => $invitado['nombre'],
                    'coHost' => isset($invitado['coHost']) && $invitado['coHost'] ? 'true' : 'false',
                    'idCatSexo' => $invitado['idCatSexo'],
                    'idCatTipoParte' => $invitado['idCatTipoParte'],
                    'idUsr' => $invitado['idUsr'] ?? null,
                    'direccion' => $invitado['direccion'],
                    'esAbogado' => $invitado['esAbogado'] ?? false,
                ]);
            }
            // Insertar historial de estado de la audiencia
            $audiencia->historialEstados()->create([
                'idCatalogoEstadoAudiencia' => 1,
                'fechaHora' => now(),
                'observaciones' => 'Audiencia programada y activa',
            ]);

            return response()->json([
                'success' => true,
                'status' => 201,
                'message' => 'Audiencia creada correctamente',
                'data' => $audiencia->load('invitados')
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear la audiencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($idAudiencia, Request $request, PermisosApiService $permisosApiService)
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

            // Cargar audiencia con relaciones necesarias
            $audiencia = Audiencia::with([
                'invitados.catSexo',
                'invitados.catTipoParte',
                'expediente',
                'ultimoEstado.catalogoEstadoAudiencia',
                'grabaciones'
            ])->findOrFail($idAudiencia);

            $ahora = now();
            if (
                $audiencia->ultimoEstado &&
                in_array($audiencia->ultimoEstado->idCatalogoEstadoAudiencia, [1, 3])
            ) {
                $fin = \Carbon\Carbon::parse($audiencia->end);
                if ($ahora->gt($fin)) {
                    // Cambia el estado a 2 (finalizada)
                    $audiencia->historialEstados()->create([
                        'idCatalogoEstadoAudiencia' => 2,
                        'fechaHora' => $ahora,
                        'observaciones' => 'Audiencia finalizada, consulte la grabación en las proximas 12 horas.',
                    ]);
                    // Recarga la relación para reflejar el cambio en la respuesta
                    $audiencia->load('ultimoEstado.catalogoEstadoAudiencia');
                }
            }

            $puedeVer = false;
            $mostrarGrabacionesCompletas = false;

            if ($esSecretario) {
                $puedeVer = optional($audiencia->expediente)->idSecretario == $idGeneral;
                $mostrarGrabacionesCompletas = $puedeVer;
            } elseif ($esAbogado) {
                $puedeVer = optional($audiencia->expediente->abogados)
                    ->contains(fn($abogado) => $abogado->idGeneral == $idGeneral);

                // Solo recibe grabaciones completas si tiene una solicitud aprobada (estado 2)
                $solicitudAprobada = Solicitudes::where('idAudiencia', $audiencia->idAudiencia)
                    ->where('idGeneral', $idGeneral)
                    ->whereHas('ultimoEstado', function ($q) {
                        $q->where('idCatalogoEstadoSolicitud', 2);
                    })
                    ->exists();

                $mostrarGrabacionesCompletas = $solicitudAprobada;
            }

            if (!$puedeVer) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tiene permisos para ver esta audiencia.',
                ], 403);
            }

            $arr = $audiencia->toArray();

            // Transformar invitados
            $arr['invitados'] = collect($audiencia->invitados)->transform(function ($invitado) {
                return [
                    'idInvitado' => $invitado->idInvitado,
                    'idUsr' => $invitado->idUsr,
                    'idAudiencia' => $invitado->idAudiencia,
                    'correo' => $invitado->correo,
                    'correoAlterno' => $invitado->correoAlterno ?? null,
                    'nombre' => $invitado->nombre,
                    'coHost' => $invitado->coHost,
                    'idCatSexo' => $invitado->idCatSexo,
                    'sexoDescripcion' => $invitado->catSexo->descripcion ?? null,
                    'idCatTipoParte' => $invitado->idCatTipoParte,
                    'tipoParteDescripcion' => $invitado->catTipoParte->descripcion ?? null,
                    'direccion' => $invitado->direccion,
                    'esAbogado' => $invitado->esAbogado ? true : false,
                ];
            });

            // Transformar el último estado para incluir la descripción del catálogo directamente
            if ($audiencia->ultimoEstado) {
                $arr['ultimo_estado'] = [
                    'idHistorialEstadoAudiencia'    => $audiencia->ultimoEstado->idHistorialEstadoAudiencia,
                    'idAudiencia'                   => $audiencia->ultimoEstado->idAudiencia,
                    'idCatalogoEstadoAudiencia'     => $audiencia->ultimoEstado->idCatalogoEstadoAudiencia,
                    'descripcion'                   => $audiencia->ultimoEstado->catalogoEstadoAudiencia->descripcion ?? null,
                    'fechaHora'                     => $audiencia->ultimoEstado->fechaHora,
                    'observaciones'                 => $audiencia->ultimoEstado->observaciones,
                    'idDocumento'                   => $audiencia->ultimoEstado->idDocumento ?? null,
                ];
            } else {
                $arr['ultimo_estado'] = null;
            }
            unset($arr['ultimoEstado']);

            // Eliminar el password de la audiencia para abogados
            if ($esAbogado && isset($arr['password'])) {
                $arr['password'] = null;
            }
            // Ajuste de grabaciones para abogados
            if ($esAbogado && isset($arr['grabaciones'])) {
                $arr['grabaciones'] = collect($arr['grabaciones'])->map(function ($grabacion) use ($mostrarGrabacionesCompletas) {
                    if (!$mostrarGrabacionesCompletas) {
                        $grabacion['downloadUrl'] = null;
                        $grabacion['playbackUrl'] = null;
                        $grabacion['password'] = null;
                    }
                    return $grabacion;
                });
            }

            if (!$esAbogado && !$mostrarGrabacionesCompletas && isset($arr['grabaciones'])) {
                unset($arr['grabaciones']);
            }

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Detalle de la audiencia',
                'data' => $arr
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'No se encontró la audiencia',
                'error' => $e->getMessage(),
            ], 404);
        }
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $idAudiencia, MeetingService $meetingService)
    {
        Log::info('Entrando a update Audiencia', ['idAudiencia' => $idAudiencia, 'request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'agenda' => 'nullable|string|max:255',
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end' => [
                'required',
                'date_format:Y-m-d H:i:s',
                'after:start',
                function ($attribute, $value, $fail) use ($request) {
                    $start = strtotime($request->input('start'));
                    $end = strtotime($value);
                    if ($end - $start < 600) { // 600 segundos = 10 minutos
                        $fail('El campo end debe ser al menos 10 minutos después de start.');
                    }
                }
            ],
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

        // Buscar la audiencia
        $audiencia = Audiencia::with('ultimoEstado')->findOrFail($idAudiencia);

        // 1. No permitir si ya está cancelada
        if ($audiencia->ultimoEstado && $audiencia->ultimoEstado->idCatalogoEstadoAudiencia == 4) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se puede actualizar una audiencia cancelada.',
            ]);
        }

        // 2. No permitir si la audiencia ya está en progreso o terminó
        $ahora = now();
        $inicio = Carbon::parse($audiencia->start);
        $fin = Carbon::parse($audiencia->end);
        if ($ahora->between($inicio, $fin) || $ahora->gt($fin)) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se puede actualizar una audiencia que ya está en progreso o finalizó.',
            ]);
        }

        // 3. No permitir si los valores enviados son iguales a los actuales
        $startDb = Carbon::parse($audiencia->start)->format('Y-m-d H:i:s');
        $endDb = Carbon::parse($audiencia->end)->format('Y-m-d H:i:s');
        $startRequest = Carbon::parse($validated['start'])->format('Y-m-d H:i:s');
        $endRequest = Carbon::parse($validated['end'])->format('Y-m-d H:i:s');

        $sinCambios =
            $startDb === $startRequest &&
            $endDb === $endRequest;

        if ($sinCambios) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se detectaron cambios en los datos de la audiencia.',
            ]);
        }

        // El meetingId de Webex está en el campo 'id'
        $meetingId = $audiencia->id;

        if (!$meetingId) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'La audiencia no tiene meetingId de Webex registrado.',
            ], 400);
        }

        // Token de Webex (puedes cambiarlo por el método que uses)
        $webexToken = env('WEBEX_TOKEN');

        // Antes de llamar a actualizarReunion:
        $startWithTz = $validated['start'] . '-06:00';
        $endWithTz = $validated['end'] . '-06:00';

        // Actualizar en Webex
        $webexResponse = $meetingService->actualizarReunion(
            $webexToken,
            $meetingId,
            array_merge($validated, [
                'start' => $startWithTz,
                'end' => $endWithTz,
            ])
        );
        Log::info('Webex response:', $webexResponse);

        if (!isset($webexResponse['webLink'])) {
            return response()->json([$webexResponse]);
        }

        // Si todo bien en Webex, actualiza en la base de datos
        $fechaInicial = $audiencia->start;
        $fechaFinal = $audiencia->end;

        // Formatear fechas para observaciones
        $fechaCarbon = Carbon::parse($fechaInicial);
        $fechaFormateada = $fechaCarbon->translatedFormat('j \d\e F \d\e Y');
        $horaInicial = Carbon::parse($fechaInicial)->format('H:i');
        $horaFinal = Carbon::parse($fechaFinal)->format('H:i');
        $observacion = "Audiencia inicialmente programada para el {$fechaFormateada}, de {$horaInicial} - {$horaFinal} h.";


        $audiencia->update([
            'title' => $validated['title'],
            'agenda' => $validated['agenda'] ?? null,
            'start' => $validated['start'],
            'end' => $validated['end'],
        ]);

        $audiencia->historialEstados()->create([
            'idCatalogoEstadoAudiencia' => 3,
            'fechaHora' => now(),
            'observaciones' => $observacion,
        ]);

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Audiencia y reunión Webex actualizadas correctamente',
            'data' => $audiencia->fresh()
        ], 200);
    }

    public function cancelarAudiencia(Request $request, $idAudiencia, NasApiService $nasApiService, MeetingService $meetingService)
    {
        $validator = Validator::make($request->all(), [
            'idCatTipoDocumento' => 'required|integer',
            'documento' => 'required|file',
            'observaciones' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'Error de validación',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Buscar la audiencia con su último estado
        $audiencia = Audiencia::with('ultimoEstado', 'expediente')->findOrFail($idAudiencia);

        // 1. No permitir si ya está cancelada
        if ($audiencia->ultimoEstado && $audiencia->ultimoEstado->idCatalogoEstadoAudiencia == 4) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se puede cancelar una audiencia que ya está cancelada.',
            ], 400);
        }

        $ahora = now();
        $inicio = Carbon::parse($audiencia->start);
        $fin = Carbon::parse($audiencia->end);

        // 2. Intenta cancelar en Webex solo si la audiencia no ha finalizado
        $webexCancelResult = null;
        if ($ahora->lte($fin)) {
            $webexToken = env('WEBEX_TOKEN');
            $meetingId = $audiencia->id; // campo 'id' en tu modelo Audiencia
            if ($meetingId) {
                $webexCancelResult = $meetingService->cancelarReunion($webexToken, $meetingId);
                // Puedes loguear el resultado si lo deseas
                Log::info('Resultado de cancelar en Webex:', ['webex' => $webexCancelResult]);
            }
        }

        try {
            // Subir documento al NAS
            $file = $request->file('documento');
            $idCatTipoDocumento = $request->input('idCatTipoDocumento');
            $numExpediente = $audiencia->expediente->NumExpediente ?? null;

            if (!$numExpediente || strpos($numExpediente, '/') === false) {
                return response()->json([
                    'success' => false,
                    'status' => 500,
                    'message' => 'El expediente relacionado no tiene el formato esperado (numero/año).',
                    'error' => $numExpediente,
                ], 500);
            }

            list($numeroExpediente, $anioExpediente) = explode('/', $numExpediente);

            $ruta = "SitiosWeb/JuicioLinea/AUDIENCIAS/{$anioExpediente}/{$numeroExpediente}";
            $timestamp = now()->format('Y_m_d_His');
            $nombreArchivo = "{$timestamp}_{$idCatTipoDocumento}_{$file->getClientOriginalName()}";

            // Subir archivo al NAS
            $nasApiService->subirArchivo($file, $ruta, $request->bearerToken(), $nombreArchivo);

            // Guardar documento en la base de datos
            $documento = Documento::create([
                'idCatTipoDocumento' => $idCatTipoDocumento,
                'nombre' => $file->getClientOriginalName(),
                'documento' => $ruta . '/' . $nombreArchivo,
                'idExpediente' => $audiencia->idExpediente,
            ]);

            $fechaCancelacion = now();
            $fechaFormateada = $fechaCancelacion->translatedFormat('j \d\e F \d\e Y');
            $horaFormateada = $fechaCancelacion->format('H:i');

            $observacionInput = $request->input('observaciones');
            $observacionBase = "Audiencia cancelada el {$fechaFormateada} a las {$horaFormateada} h.";

            if ($observacionInput) {
                $observacion = "{$observacionInput}. {$observacionBase}";
            } else {
                $observacion = $observacionBase;
            }

            // Crear historial de estado con idDocumento
            $audiencia->historialEstados()->create([
                'idCatalogoEstadoAudiencia' => 4,
                'fechaHora' => now(),
                'observaciones' => $observacion,
                'idDocumento' => $documento->idDocumento,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Estado de la audiencia y documento actualizados correctamente',
                'webex' => $webexCancelResult,
                'data' => $audiencia->fresh('historialEstados')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al actualizar el estado de la audiencia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audiencia $audiencia)
    {
        //
    }

    public function disponibilidad(Request $request, PermisosApiService $permisosApiService)
    {
        $fecha = $request->query('fecha'); // formato: YYYY-MM-DD
        $idAudiencia = $request->query('idAudiencia'); // opcional

        // Obtener el idGeneral del token
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);
        $idGeneral = $datosUsuario['idGeneral'] ?? null;

        if (!$fecha) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar el parámetro fecha en formato YYYY-MM-DD'
            ], 400);
        }
        if (!$idGeneral) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo obtener el idGeneral del token'
            ], 400);
        }

        // Rango de 07:00 a 22:00
        $inicioDia = Carbon::parse($fecha . ' 07:00:00');
        $finDia = Carbon::parse($fecha . ' 22:00:00');

        // Solo audiencias de expedientes donde expediente.idSecretario = idGeneral
        $audiencias = Audiencia::whereDate('start', $fecha)
            ->whereHas('expediente', function ($q) use ($idGeneral) {
                $q->where('idSecretario', $idGeneral);
            })
            ->orderBy('start')
            ->get(['start', 'end', 'idAudiencia']);
        $ocupados = $audiencias->map(function ($a) {
            return [
                'start' => Carbon::parse($a->start)->format('H:i'),
                'end' => Carbon::parse($a->end)->format('H:i'),
            ];
        })->toArray();

        // Si se recibe idAudiencia, elimina ese periodo de los ocupados (lo trata como disponible)
        if ($idAudiencia) {
            $audiencia = $audiencias->firstWhere('idAudiencia', $idAudiencia);
            if ($audiencia) {
                $ocupados = array_filter($ocupados, function ($o) use ($audiencia) {
                    return !(
                        $o['start'] === Carbon::parse($audiencia->start)->format('H:i') &&
                        $o['end'] === Carbon::parse($audiencia->end)->format('H:i')
                    );
                });
            }
        }

        // Calcular los rangos libres
        $libres = [];
        $prevEnd = $inicioDia->copy();

        // Si se recibió idAudiencia, agrega ese periodo como libre
        if ($idAudiencia && isset($audiencia)) {
            $libres[] = [
                'start' => Carbon::parse($audiencia->start)->copy(),
                'end' => Carbon::parse($audiencia->end)->copy(),
            ];
        }

        foreach ($ocupados as $o) {
            $start = Carbon::parse($fecha . ' ' . $o['start']);
            if ($start->gt($prevEnd)) {
                $libres[] = [
                    'start' => $prevEnd->copy(),
                    'end' => $start->copy(),
                ];
            }
            $prevEnd = Carbon::parse($fecha . ' ' . $o['end'])->gt($prevEnd) ? Carbon::parse($fecha . ' ' . $o['end']) : $prevEnd;
        }
        if ($prevEnd->lt($finDia)) {
            $libres[] = [
                'start' => $prevEnd->copy(),
                'end' => $finDia->copy(),
            ];
        }

        // Dividir los rangos libres en bloques de 30 minutos
        $disponibles = [];
        $ahora = Carbon::now();
        $esHoy = $fecha === $ahora->format('Y-m-d');

        foreach ($libres as $rango) {
            $slotStart = $rango['start']->copy();
            while ($slotStart->lt($rango['end'])) {
                $slotEnd = $slotStart->copy()->addMinutes(30);
                if ($slotEnd->gt($rango['end'])) {
                    $slotEnd = $rango['end']->copy();
                }
                // Solo mostrar bloques futuros si es hoy
                if (!$esHoy || $slotStart->gte($ahora)) {
                    if ($slotStart->lt($slotEnd)) {
                        $disponibles[] = $slotStart->format('H:i');
                    }
                }
                $slotStart = $slotEnd->copy();
            }
        }

        // Si es hoy y no hay bloques disponibles, mostrar el siguiente bloque de 30 minutos futuro
        if ($esHoy && empty($disponibles)) {
            $proximo = $ahora->copy()->ceilMinute(30);
            if ($proximo->between($inicioDia, $finDia)) {
                $disponibles[] = $proximo->format('H:i');
            }
        }

        $disponibles = array_values(array_unique($disponibles));
        sort($disponibles);

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Disponibilidad de horarios consultada correctamente',
            'data' => [
                'fecha' => $fecha,
                'ocupados' => array_values($ocupados),
                'disponibles' => $disponibles,
            ]
        ], 200);
    }

    public function rangoMaximoDisponible(Request $request)
    {
        $fecha = $request->query('fecha'); // formato: YYYY-MM-DD
        $horaInicio = $request->query('start'); // formato: HH:mm
        $idAudiencia = $request->query('idAudiencia'); // opcional

        // Obtener el idGeneral del token
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);
        $idGeneral = $datosUsuario['idGeneral'] ?? null;

        if (!$fecha || !$horaInicio) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Debes enviar los parámetros fecha (YYYY-MM-DD) y start (HH:mm)'
            ], 400);
        }
        if (!$idGeneral) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se pudo obtener el idGeneral del token'
            ], 400);
        }

        // Rango de 07:00 a 22:00
        $inicioDia = Carbon::parse($fecha . ' 07:00:00');
        $finDia = Carbon::parse($fecha . ' 22:00:00');

        // Solo audiencias de expedientes donde expediente.idSecretario = idGeneral
        $audiencias = Audiencia::whereDate('start', $fecha)
            ->whereHas('expediente', function ($q) use ($idGeneral) {
                $q->where('idSecretario', $idGeneral);
            })
            ->orderBy('start')
            ->get(['start', 'end', 'idAudiencia']);

        // Si se recibe idAudiencia, elimina ese periodo de los ocupados (lo trata como libre)
        if ($idAudiencia) {
            $audiencia = $audiencias->firstWhere('idAudiencia', $idAudiencia);
            if ($audiencia) {
                $audiencias = $audiencias->filter(function ($a) use ($audiencia) {
                    return $a->idAudiencia != $audiencia->idAudiencia;
                });
            }
        }

        // Calcular los rangos libres
        $libres = [];
        $prevEnd = $inicioDia->copy();

        foreach ($audiencias as $audienciaOcupada) {
            $start = Carbon::parse($audienciaOcupada->start);
            if ($start->gt($prevEnd)) {
                $libres[] = [
                    'start' => $prevEnd->copy(),
                    'end' => $start->copy(),
                ];
            }
            $prevEnd = Carbon::parse($audienciaOcupada->end)->gt($prevEnd) ? Carbon::parse($audienciaOcupada->end) : $prevEnd;
        }
        if ($prevEnd->lt($finDia)) {
            $libres[] = [
                'start' => $prevEnd->copy(),
                'end' => $finDia->copy(),
            ];
        }

        // Si se recibió idAudiencia, agrega ese periodo como libre
        if ($idAudiencia && isset($audiencia)) {
            $libres[] = [
                'start' => Carbon::parse($audiencia->start)->copy(),
                'end' => Carbon::parse($audiencia->end)->copy(),
            ];
        }

        // Buscar el rango máximo disponible desde la hora indicada
        $rangoMaximo = null;
        $horaInicioCarbon = Carbon::parse($fecha . ' ' . $horaInicio . ':00');
        foreach ($libres as $rango) {
            if ($horaInicioCarbon->gte($rango['start']) && $horaInicioCarbon->lt($rango['end'])) {
                $rangoMaximo = [
                    'start' => $horaInicioCarbon->copy(),
                    'end' => $rango['end']->copy(),
                    'minutos_disponibles' => $horaInicioCarbon->diffInMinutes($rango['end']),
                ];
                break;
            }
        }

        if (!$rangoMaximo || $rangoMaximo['minutos_disponibles'] < 30) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No hay al menos 30 minutos disponibles, elija otra fecha u hora',
                'data' => null
            ], 400);
        }

        // Generar bloques de 30 minutos dentro del rango máximo, agregando la duración acumulada
        $bloques = [];
        $slotStart = $rangoMaximo['start']->copy();
        $slotEnd = $rangoMaximo['end']->copy();
        $acumulado = 0;
        while ($slotStart->lt($slotEnd)) {
            $nextSlot = $slotStart->copy()->addMinutes(30);
            if ($nextSlot->gt($slotEnd)) {
                break;
            }
            $acumulado += 30;
            $horas = intdiv($acumulado, 60);
            $minutos = $acumulado % 60;
            $duracion = [];
            if ($horas > 0) $duracion[] = $horas . 'h';
            if ($minutos > 0) $duracion[] = $minutos . ' min';
            $bloques[] = $nextSlot->format('H:i') . ' (' . implode(', ', $duracion) . ')';
            $slotStart = $nextSlot;
        }

        return response()->json([
            'success' => true,
            'status' => 200,
            'message' => 'Rango máximo disponible consultado correctamente',
            'data' => [
                'fecha' => $fecha,
                'start' => $horaInicio,
                'end' => $bloques,
            ]
        ], 200);
    }
}
