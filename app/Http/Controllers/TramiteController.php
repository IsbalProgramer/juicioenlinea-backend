<?php

namespace App\Http\Controllers;

use App\Helpers\FolioHelper;
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

            // $perfiles = $permisosApiService->obtenerPerfilesUsuario($request->bearerToken(), $idSistema);
            // $tienePerfilAbogado = collect($perfiles)->contains(function ($perfil) {
            //     return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
            // });

            // if (!$tienePerfilAbogado) {
            //     return response()->json([
            //         'status' => 403,
            //         'message' => 'No tiene permisos para realizar esta acción.',
            //     ], 403);
            // }

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

            // Si no hay filtros, usar últimos 7 días por defecto
            if (!$fechaInicio && !$fechaFinal && !$folioOficio && !$estado && !$tipo) {
                $inicio = Carbon::now($timezone)->subDays(6)->startOfDay();
                $fin = Carbon::now($timezone)->endOfDay();
            }

            // Base query
            $query = Tramite::with(['historial', 'catTramite']);

            // Folio
            if ($folioOficio) {
                $query->where('folioOficio', 'like', "%$folioOficio%");
            }

            // Tipo de trámite
            if ($tipo) {
                $query->where('idCatTramite', $tipo);
            }

            // Filtrado por historial
            if ($inicio && $fin) {
                $query->whereHas('historial', function ($q) use ($inicio, $fin) {
                    $q->whereBetween('created_at', [$inicio, $fin]);
                });
            }

            // Obtener trámites
            $tramites = $query->get()
                ->filter(function ($tramite) use ($estado) {
                    $ultimoEstado = $tramite->historial->last()->idCatEstadoTramite ?? null;

                    if (is_null($estado) || $estado == 0) return true;

                    return $ultimoEstado == $estado;
                })
                ->where('idGeneral', $idGeneral)
                ->sortByDesc(fn($tramite) => optional($tramite->historial->last())->created_at)
                ->values();

            return response()->json([
                'status' => 200,
                'message' => 'Listado de trámites',
                'data' => $tramites
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
            'idExpediente'     => 'required|exists:expedientes,idExpediente',
            'idCatTramite'     => 'required|exists:cat_tramites,idCatTramite',
            // 'idGeneral'        => 'required|integer',  logeo 
            // 'tramiteOrigen'    => 'required|string|max:255',
            // 'folioOficio'      => 'required|string|max:255',
            // 'idPr' => 'required|string|max:255',
            'sintesis'         => 'required|string',
            'observaciones'    => 'required|string',
            // 'fechaRecepcion'   => 'required|date',
            'documentoTramite' => 'required|file|mimes:pdf,doc,docx',
        ]);

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

            // Subir documento 
            $documentoTramite = $request->file('documentoTramite');
            $nombreOriginal = pathinfo($documentoTramite->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $documentoTramite->getClientOriginalExtension();
            $timestamp = now()->format('Ymd_His');
            $nuevoNombre = "{$nombreOriginal}_{$timestamp}.{$extension}";

            // Obtener número de expediente
            $expediente = DB::table('expedientes')->where('idExpediente', $request->idExpediente)->value('NumExpediente');
            $expedienteRuta = implode('/', array_reverse(explode('/', $expediente)));
            $ruta = "PERICIALES/JUZGADOS/{$expedienteRuta}/TRAMITES";

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

            // Guardar documento en BD
            $documento = Documento::create([
                'idCatTipoDocumento' => -1, // Asume que ya tienes un ID para tipo trámite
                'nombre' => 'TRAMITE',
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
                'usr' => $usr,
                'folioOficio' => $folioTramite,
                'sintesis' => $request->sintesis,
                'observaciones' => $request->observaciones,
                'idExpediente' => $request->idExpediente,
                'idDocumentoTramite' => $documento->idDocumento, // Aquí se asigna el ID del documento
            ]);

            // Crear historial de trámite
            $historial = HistorialEstadoTramite::create([
                'idTramite' => $tramite->idTramite,
                // 'idUsuario' => $idGeneral,
                'idCatEstadoTramite' => 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Trámite y documento creados exitosamente',
                'data' => [
                    'tramite' => $tramite,
                    'documento' => $documento,
                    'historial' => $historial
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
                'expediente',
                'historial',
                'documento',
                'catTramite'
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
