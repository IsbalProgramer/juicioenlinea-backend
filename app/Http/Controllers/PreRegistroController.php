<?php

namespace App\Http\Controllers;

use App\Models\Catalogos\CatMateriaVia;
use App\Models\PreRegistro;
use App\Models\Parte;
use App\Services\MailerSendService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;
use App\Services\NasApiService;
use App\Services\PermisosApiService;
use Carbon\Carbon;

class PreRegistroController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {
            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];

            $fechaInicioParam = $request->query('fechaInicio');
            $fechaFinalParam = $request->query('fechaFinal');
            $folio = $request->query('folio');
            $estado = $request->query('estado');

            $fechaInicio = null;
            $fechaFinal = null;

            // Aplicar filtro de fechas si se mandan
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
            } elseif (!$folio) {
                $fechaInicio = Carbon::now()->subDays(6)->startOfDay();
                $fechaFinal = Carbon::now()->endOfDay();
            }

            $preRegistros = PreRegistro::with([
                'partes',
                'documentos:idPreregistro,nombre',
                'catMateriaVia.catMateria',
                'catMateriaVia.catVia',
                'historialEstado' => function ($query) {
                    $query->latest('fechaEstado')
                        ->limit(1)
                        ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
                        ->with('estado:idCatEstadoInicio,descripcion');
                }
            ])
                ->where('idGeneral', $idGeneral)
                ->when($folio, function ($query) use ($folio) {
                    $query->where('folioPreregistro', 'like', "%{$folio}%");
                })
                ->when($fechaInicio && $fechaFinal, function ($query) use ($fechaInicio, $fechaFinal) {
                    $query->whereBetween('created_at', [$fechaInicio, $fechaFinal]);
                })
                ->when($estado, function ($query) use ($estado) {
                    // Filtrar por el estado más reciente en historialEstado
                    $query->whereHas('historialEstado', function ($q) use ($estado) {
                        $q->latest('fechaEstado')
                          ->limit(1)
                          ->where('idCatEstadoInicio', $estado);
                    });
                })

                // ->filter(function ($q) use ($estado) {
                //    $q->latest('fechaEstado')
                //           ->limit(1)
                //           ->where('idCatEstadoInicio', $estado);
                //     if (is_null($estado)) {
                //         // Por defecto, estado 3
                //         return $q == 1;
                //     }

                //     if ($estado === '0' || $estado === 0) {
                //         // No aplicar filtro
                //         return true;
                //     }

                //     // Filtro por estado específico (ej. 1-5)
                //     return $q == $estado;
                // })
                ->get();

            return response()->json([
                'status' => 200,
                'message' => "Listado de preregistros",
                'data' => $preRegistros
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de preregistros',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, NasApiService $nasApiService, PermisosApiService $permisosApiService)
    {
        $validator = Validator::make($request->all(), [
            'idCatMateria' => 'required|integer',
            'idCatTipoVia' => 'required|integer',
            'sintesis' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
            'partes' => 'required|array|min:1',
            'partes.*.nombre' => 'required|string|max:255',
            'partes.*.apellidoPaterno' => 'required|string|max:255',
            'partes.*.apellidoMaterno' => 'nullable|string|max:255',
            'partes.*.idCatSexo' => 'required|integer',
            'partes.*.idCatTipoParte' => 'required|integer',
            'partes.*.direccion' => 'nullable|string|max:255',
            'documentos.*.idCatTipoDocumento' => 'required|integer',
            'documentos.*.nombre' => 'nullable|string',
            'documentos.*.documento' => 'required|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            DB::beginTransaction(); // Iniciar transacción

            // Validar datos antes de cualquier inserción
            if (!$request->has('partes') || count($request->partes) === 0) {
                throw new \Exception('Debe incluir al menos una parte.');
            }

            if (!$request->has('documentos') || count($request->documentos) === 0) {
                throw new \Exception('Debe incluir al menos un documento.');
            }

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
                    'message' => 'No se pudo obtener el idGeneral',
                ], 400);
            }

            // Obtener idAreaSistemaUsuario
            $token = $request->bearerToken();
            $idAreaSistemaUsuario = $permisosApiService->obtenerIdAreaSistemaUsuario($token, $idGeneral);

            if (!$idAreaSistemaUsuario) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No se pudo obtener el área del usuario.',
                ], 403);
            }

            // Obtener perfiles del usuario
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($token, $idAreaSistemaUsuario);

            // Validar que tenga el perfil "Abogado"
            $tienePerfilAbogado = false;
            if (is_array($perfiles)) {
                foreach ($perfiles as $perfil) {
                    if (isset($perfil['descripcion']) && strtolower($perfil['descripcion']) === 'abogado') {
                        $tienePerfilAbogado = true;
                        break;
                    }
                }
            }

            if (!$tienePerfilAbogado) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No tienes permisos para realizar esta acción.',
                ], 403);
            }

            // Crear el folio consecutivo
            $ultimoFolio = PreRegistro::latest('idPreregistro')->value('folioPreregistro');
            $numeroConsecutivo = $ultimoFolio ? intval(explode('/', $ultimoFolio)[0]) + 1 : 1;
            $anio = now()->year;
            $folioPreregistro = str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio;

            // Crear el registro en la tabla pivote "cat_materia_via"
            $catMateriaVia = CatMateriaVia::firstOrCreate([
                'idCatMateria' => $request->idCatMateria,
                'idCatTipoVia' => $request->idCatTipoVia,
            ]);


            $preRegistro = PreRegistro::create([
                'folioPreregistro' => $folioPreregistro,
                'idCatMateriaVia' => $catMateriaVia->idCatMateriaVia,
                'idGeneral' => $idGeneral,
                'usr' => $usr,
                'fechaCreada' => now(),
                'sintesis' => $request->sintesis,
                'observaciones' => $request->observaciones,
            ]);

            // Crear el registro en la tabla "historial_estado_inicios"
            $preRegistro->historialEstado()->create([
                'idCatEstadoInicio' => 1,
                'fechaEstado' => now(),
            ]);

            // Insertar las partes asociadas
            $preRegistro->partes()->createMany($request->partes);

            // Subir documentos al NAS y preparar datos para la base
            $documentos = [];
            $folioSoloNumero = explode('/', $folioPreregistro)[0];
            foreach ($request->documentos as $documento) {
                $file = $documento['documento'];
                $idCatTipoDocumento = $documento['idCatTipoDocumento'] ?? -1;
                $nombreDocumento = $documento['nombre'] ?? 'documento';

                $ruta = "PERICIALES/PREREGISTROS/{$anio}/{$folioSoloNumero}";

                // año_mes_dia_segundos_idTipoDocumento_nombreDocumento.ext
                $timestamp = now()->format('Y_m_d_His');
                $nombreArchivo = "{$timestamp}_{$idCatTipoDocumento}_{$nombreDocumento}.{$file->getClientOriginalExtension()}";

                // Subir archivo al NAS
                $nasApiService->subirArchivo($file, $ruta, $request->bearerToken(), $nombreArchivo);

                // Guardar en la base: nombre solo si idCatTipoDocumento == -1, si no, null
                $documentos[] = [
                    'idCatTipoDocumento' => $idCatTipoDocumento,
                    'nombre' => $idCatTipoDocumento == -1 ? $nombreDocumento : null,
                    'documento' => $ruta . '/' . $nombreArchivo,
                ];
            }

            // Insertar los documentos asociados a este preregistro
            $preRegistro->documentos()->createMany($documentos);

            DB::commit(); // Confirmar transacción
            $mailerSend = new MailerSendService();
            $mailerSend->enviarCorreo(
                "dvirdr2@gmail.com", // destinatario, puedes hacerlo dinámico
                "Confirmación de preregistro #{$preRegistro->folioPreregistro}",
                [
                    'order_number' => $preRegistro->folioPreregistro,
                    "tracking_number" => $preRegistro->folioPreregistro,
                    "date" => $preRegistro->fechaCreada,
                    "delivery" => $preRegistro->catMateriaVia->catVia->descripcion,
                    "delivery_date" => $preRegistro->observaciones,
                    "address" => $preRegistro->sintesis,
                    "support_email" => "dvirdr2@gmail.com"
                ],
                "zr6ke4n8p5e4on12" // tu template_id
            );
            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Pre-Registro creado exitosamente',
                'data' => $preRegistro->makeHidden(['documentos.documento']),
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear el preregistro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    // public function show(Request $request, PermisosApiService $permisosApiService, $idPreregistro)
    // {
    //     try {
    //         // Obtener el payload del token desde los atributos de la solicitud
    //         $jwtPayload = $request->attributes->get('jwt_payload');
    //         $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

    //         if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
    //             return response()->json([
    //                 'status' => 400,
    //                 'message' => 'No se pudo obtener el idGeneral del token',
    //             ], 400);
    //         }

    //         $idGeneral = $datosUsuario['idGeneral'];

    //         $preRegistro = PreRegistro::with([
    //             'partes.catTipoParte',
    //             'partes.catSexo',
    //             'documentos.catTipoDocumento',
    //             'catMateriaVia.catMateria',
    //             'catMateriaVia.catVia',
    //             'historialEstado' => function ($query) {
    //                 $query->latest('fechaEstado')
    //                     ->limit(1)
    //                     ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
    //                     ->with('estado:idCatEstadoInicio,descripcion');
    //             }
    //         ])
    //             ->where('idGeneral', $idGeneral)
    //             ->findOrFail($idPreregistro);

    //         // Transformar las partes para incluir solo los datos necesarios
    //         $preRegistro->partes->transform(function ($parte) {
    //             return [
    //                 'idParte' => $parte->idParte,
    //                 'idPreregistro' => $parte->idPreregistro,
    //                 'nombre' => $parte->nombre,
    //                 'apellidoPaterno' => $parte->apellidoPaterno,
    //                 'apellidoMaterno' => $parte->apellidoMaterno,
    //                 'direccion' => $parte->direccion,
    //                 'idCatSexo' => $parte->idCatSexo,
    //                 'sexoDescripcion' => $parte->catSexo->descripcion ?? null, // Solo la descripción del catálogo
    //                 'idCatTipoParte' => $parte->idCatTipoParte,
    //                 'tipoParteDescripcion' => $parte->catTipoParte->descripcion ?? null, // Solo la descripción del catálogo
    //             ];
    //         });

    //         // Modificar los documentos para asignar el nombre desde el catálogo si es null
    //         $preRegistro->documentos->transform(function ($documento) {
    //             // Si el nombre es null y hay un idCatTipoDocumento, asignar el nombre desde el catálogo
    //             if (is_null($documento->nombre) && $documento->catTipoDocumento) {
    //                 $documento->nombre = $documento->catTipoDocumento->nombre; // Asignar el nombre desde el catálogo
    //             }
    //             return $documento;
    //         });

    //         return response()->json([
    //             'status' => 200,
    //             'message' => "Detalle del preregistro",
    //             'data' => $preRegistro
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'message' => "No se encontró el registro",
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    public function show(Request $request, PermisosApiService $permisosApiService, $idPreregistro)
    {
        try {
            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];

            // Obtener el sistema y perfiles
            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $idGeneral, 4171);
            if (!$idSistema) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario',
                ], 400);
            }

            $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            if (!$perfiles) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener los perfiles del usuario',
                ], 400);
            }

            $esAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
            });

            $esSecretario = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            });

            if (!$esAbogado && !$esSecretario) {
                return response()->json([
                    'status' => 403,
                    'message' => 'No tiene permisos para realizar esta acción.',
                ], 403);
            }

            // Consulta del preregistro dependiendo del rol
            $query = PreRegistro::with([
                'partes.catTipoParte',
                'partes.catSexo',
                'documentos.catTipoDocumento',
                'catMateriaVia.catMateria',
                'catMateriaVia.catVia',
                'historialEstado' => function ($query) {
                    $query->latest('fechaEstado')
                        ->limit(1)
                        ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
                        ->with('estado:idCatEstadoInicio,descripcion');
                }
            ]);

            if ($esAbogado) {
                $query->where('idGeneral', $idGeneral);
            } elseif ($esSecretario) {
                // Los secretarios pueden ver cualquier preregistro, no se limita por idGeneral
            }

            $preRegistro = $query->findOrFail($idPreregistro);

            // Transformar las partes
            $preRegistro->partes->transform(function ($parte) {
                return [
                    'idParte' => $parte->idParte,
                    'idPreregistro' => $parte->idPreregistro,
                    'nombre' => $parte->nombre,
                    'apellidoPaterno' => $parte->apellidoPaterno,
                    'apellidoMaterno' => $parte->apellidoMaterno,
                    'direccion' => $parte->direccion,
                    'idCatSexo' => $parte->idCatSexo,
                    'sexoDescripcion' => $parte->catSexo->descripcion ?? null,
                    'idCatTipoParte' => $parte->idCatTipoParte,
                    'tipoParteDescripcion' => $parte->catTipoParte->descripcion ?? null,
                ];
            });

            // Transformar documentos
            $preRegistro->documentos->transform(function ($documento) {
                if (is_null($documento->nombre) && $documento->catTipoDocumento) {
                    $documento->nombre = $documento->catTipoDocumento->nombre;
                }
                return $documento;
            });

            return response()->json([
                'status' => 200,
                'message' => "Detalle del preregistro",
                'data' => $preRegistro
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PreRegistro $preRegistro)
    {
        // Valida solo los campos permitidos
        $validator = Validator::make($request->all(), [
            'idCatJuzgado' => 'required|integer',
            'idExpediente' => 'required',
            'fechaResponse' => 'required|date',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            $preRegistro->update([
                'idCatJuzgado' => $request->idCatJuzgado,
                'idExpediente' => $request->idExpediente,
                'fechaResponse' => $request->fechaResponse,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'PreRegistro actualizado correctamente',
                'data' => $preRegistro->fresh()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al actualizar el preregistro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PreRegistro $preRegistro)
    {
        //
    }
}
