<?php

namespace App\Http\Controllers;

use App\Helpers\FolioHelper;
use App\Models\Abogado;
use App\Models\Documento;
use App\Models\HistorialEstadoTramite;
use App\Models\Tramite;
use App\Services\PermisosApiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use PhpParser\Node\Expr\FuncCall;

class TramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener los datos del usuario desde el token.',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario($request->bearerToken(), $idGeneral, 4171);

            if (!$idSistema) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idAreaSistemaUsuario.',
                ], 400);
            }

            // Filtros
            $estado = $request->query('estado');
            $fechaInicio = $request->query('fechaInicio');
            $fechaFinal = $request->query('fechaFinal');
            $folioOficio = $request->query('folio');
            $tipo = $request->query('tipo');
            $timezone = config('app.timezone', 'America/Mexico_City');

            // Fechas por defecto
            $inicio = $fechaInicio ? Carbon::parse($fechaInicio, $timezone)->startOfDay() : null;
            $fin = $fechaFinal ? Carbon::parse($fechaFinal, $timezone)->endOfDay() : null;

            if (!$fechaInicio && !$fechaFinal && !$folioOficio && !$estado && !$tipo) {
                $inicio = Carbon::now($timezone)->subDays(6)->startOfDay();
                $fin = Carbon::now($timezone)->endOfDay();
            }

            // Base query
            $query = Tramite::with([
                'historial',
                'catTramite',
                'expediente.juzgado', // <--- Agrega esta línea
            ])->where('idGeneral', $idGeneral);

            if ($folioOficio) {
                $query->where('folioOficio', 'like', "%$folioOficio%");
            }

            if ($tipo) {
                $query->where('idCatTramite', $tipo);
            }

            if ($inicio && $fin) {
                $query->whereHas('historial', function ($q) use ($inicio, $fin) {
                    $q->whereBetween('created_at', [$inicio, $fin]);
                });
            }

            // Ahora traemos TODO el dataset filtrado en BD
            $all = $query->get()
                ->sortByDesc(function ($tramite) {
                    return optional($tramite->historial->last())->created_at;
                })
                ->values();

            // Paginación manual
            $perPage = (int)$request->query('per_page', 10);
            $page = (int)$request->query('page', 1);

            $total = $all->count();
            $lastPage = (int)ceil($total / $perPage);

            $pageItems = $all->forPage($page, $perPage)->values();

            return response()->json([
                'status' => 200,
                'message' => 'Listado de trámites',
                'data' => $pageItems,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => $lastPage,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener los trámites',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, PermisosApiService $permisosApiService)
    {
        $validator = Validator::make($request->all(), [
            'idExpediente'     => 'required|',
            'idCatTramite'     => 'required|exists:cat_tramites,idCatTramite',
            'sintesis'         => 'required|string',
            'observaciones'    => 'required|string',
            'documentoTramite' => 'required|file|mimes:pdf,doc,docx',
            'partes' => 'nullable|array|min:1',
            'partes.*.idUsr' => 'nullable|integer',
            'partes.*.nombre' => 'required|string|max:255',
            'partes.*.apellidoPaterno' => 'string|max:255',
            'partes.*.apellidoMaterno' => 'string|max:255',
            'partes.*.idCatSexo' => 'required|integer',
            'partes.*.idCatTipoParte' => 'required|integer',
            'partes.*.correo' => 'required|email|max:255',
            'partes.*.direccion' => 'nullable|string|max:255',
            'idCatRemitente' => 'nullable|exists:cat_remitentes,idCatRemitente',
        ]);

        // dd($request->all());

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $errorMessage = implode(', ', $errors);
            return response()->json([
                'status' => 422,
                'message' => $errorMessage,
            ], 422);
        }

        try {
            DB::beginTransaction();

            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral']) || !isset($datosUsuario['Usr'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener los datos del token',
                ], 400);
            }



            $idGeneral = $datosUsuario['idGeneral'];
            $usr = $datosUsuario['Usr'];

            // Obtener ID del sistema (puedes ajustar el ID real si es diferente)
            $idSistema = $permisosApiService->obtenerIdAreaSistemaUsuario(
                $request->bearerToken(),
                $idGeneral,
                4171 // ID de tu sistema
            );

            if (!$idSistema) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el sistema del usuario.',
                ], 400);
            }

            // Obtener perfiles del usuario
            $perfiles = $permisosApiService->obtenerPerfilesUsuario(
                $request->bearerToken(),
                $idSistema
            );

            if (!$perfiles) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudieron obtener los perfiles del usuario.',
                ], 400);
            }


            $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
                return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
            });


            if ($tienePerfilAbogado) {
                $token = $request->bearerToken();

                $respuesta = $permisosApiService->obtenerDatosUsuarioByApi($token, $usr);

                if (!empty($respuesta['data']['pD_Abogados']) && is_array($respuesta['data']['pD_Abogados'])) {
                    foreach ($respuesta['data']['pD_Abogados'] as $abogadoData) {
                        if (isset($abogadoData['idGeneral']) && $abogadoData['idGeneral'] == $idGeneral) {
                            // Registrar abogado si no existe
                            $abogado = Abogado::firstOrCreate(
                                [
                                    'idUsr' => $usr,
                                    'idGeneral' => $idGeneral,
                                ],
                                [
                                    'nombre' => mb_strtoupper($abogadoData['nombre'] ?? ''),
                                    'correo' => mb_strtoupper($abogadoData['correo'] ?? ''),
                                    'correoAlterno' => mb_strtoupper($abogadoData['correoAlterno'] ?? ''),
                                ]
                            );

                            // Verificar si ya está enlazado al expediente
                            $yaRelacionado = DB::table('expediente_abogado')
                                ->where('idExpediente', $request->idExpediente)
                                ->where('idAbogado', $abogado->idAbogado)
                                ->exists();

                            if (!$yaRelacionado) {
                                DB::table('expediente_abogado')->insert([
                                    'idExpediente' => $request->idExpediente,
                                    'idAbogado' => $abogado->idAbogado,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }

                            break; // Solo una coincidencia necesaria
                        }
                    }
                }
            }


            // Subir documento 
            $documentoTramite = $request->file('documentoTramite');
            $nombreOriginal = pathinfo($documentoTramite->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $documentoTramite->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $nuevoNombre = "{$nombreOriginal}_{$timestamp}.{$extension}";

            // Obtener número de expediente
            $expediente = DB::table('expedientes')->where('idExpediente', $request->idExpediente)->value('NumExpediente');
            $expedienteRuta = implode('/', array_reverse(explode('/', $expediente)));
            $ruta = "SitiosWeb/JuicioLinea/JUZGADOS/{$expedienteRuta}/TRAMITES";

            // Subir a NAS
            $response = Http::withToken($request->bearerToken())
                ->attach('file', file_get_contents($documentoTramite), $nuevoNombre)
                ->post('https://api.tribunaloaxaca.gob.mx/NasApi/api/Nas', ['path' => $ruta]);

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al subir el documento',
                    'error' => $response->json(),
                ], 500);
            }

            // Obtener nombre del trámite en mayúsculas
            $nombreTramite = DB::table('cat_tramites')
                ->where('idCatTramite', $request->idCatTramite)
                ->value('nombre');

            $nombreTramiteMayus = mb_strtoupper($nombreTramite, 'UTF-8');

            // Guardar documento en BD con nombre dinámico
            $documento = Documento::create([
                'idCatTipoDocumento' => -1,
                'nombre' => $nombreTramiteMayus, // ← Aquí se usa en mayúsculas
                'idExpediente' => $request->idExpediente,
                'documento' => $ruta . '/' . $nuevoNombre,
            ]);


            $ultimoFolio = Tramite::latest('idTramite')->value('folioOficio');
            $numeroConsecutivo = $ultimoFolio ? intval(explode('/', $ultimoFolio)[0]) + 1 : 1;
            $anio = now()->year;
            $folioTramite = str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio;


            // Crear trámite
            $tramite = Tramite::create([
                'idCatTramite' => $request->idCatTramite,
                'idGeneral' => $idGeneral,
                // 'usr' => $usr,
                'idUsr' => $usr,
                'folioOficio' => $folioTramite,
                'sintesis' => $request->sintesis,
                'observaciones' => $request->observaciones,
                'idExpediente' => $request->idExpediente,
                'idDocumentoTramite' => $documento->idDocumento, // Aquí se asigna el ID del documento
                'idCatRemitente' => $request->idCatRemitente, // Permite nulo si no viene en la request
            ]);

            $partesProcesadas = [];

            if (is_array($request->partes)) {
                foreach ($request->partes as $parte) {
                    if (!empty($parte['idUsr'])) {
                        $nombreFinal = $parte['nombre'];
                    } else {
                        $nombreFinal = trim(
                            $parte['nombre'] . ' ' .
                                ($parte['apellidoPaterno'] ?? '') . ' ' .
                                ($parte['apellidoMaterno'] ?? '')
                        );
                    }

                    $parteProcesada = $parte;
                    $parteProcesada['nombre'] = $nombreFinal;

                    unset($parteProcesada['apellidoPaterno'], $parteProcesada['apellidoMaterno']);

                    $partesProcesadas[] = $parteProcesada;
                }
            } else {
                // Manejo en caso de partes nulas
                $partesProcesadas = [];
            }

            // Insertar las partes asociadas
            $tramite->partesTramite()->createMany($partesProcesadas);

            // Crear historial de trámite
            $historial = HistorialEstadoTramite::create([
                'idTramite' => $tramite->idTramite,
                'idCatEstadoTramite' => 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Trámite y documento creados exitosamente',
                'data' => [
                    'tramite' => $tramite,
                    'documento' => $documento,
                    'historial' => $historial,
                    'partes' => $partesProcesadas,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 500,
                'message' => 'Error al crear el trámite',
                'error' => $e->getMessage()
            ]);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Tramite $tramite, $idTramite)
    {
        try {
            $tramite = Tramite::with([
                'expediente.preRegistro.partes.catTipoParte',
                'historial',
                'documento',
                'catTramite',
                'partesTramite.catTipoParte',
                'remitente',
                'expediente.juzgado',
            ])->findOrFail($idTramite);
            return response()->json([
                'status' => 200,
                'message' => "Detalle del tramite",
                'data' => $tramite
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $tramite = Tramite::find($id);

        if (!$tramite) {
            return response()->json([
                'status' => 404,
                'message' => 'Trámite no encontrado.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'notificado' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'message' => implode(', ', $validator->messages()->all()),
            ], 422);
        }

        $tramite->notificado = $request->notificado;
        $tramite->save();

        return response()->json([
            'status' => 200,
            'message' => 'El campo notificado ha sido actualizado correctamente.',
            'data' => $tramite
        ]);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Tramite $tramite)
    {
        //
    }
}
