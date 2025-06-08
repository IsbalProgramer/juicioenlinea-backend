<?php

namespace App\Http\Controllers;

use App\Helpers\FolioHelper;
use App\Models\Documento;
use App\Models\HistoriaEstadoTramite;
use App\Models\Tramite;
use App\Services\PermisosApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;


class TramiteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    /**
     * Store a newly created resource in storage.
     */
   public function store(Request $request, PermisosApiService $permisosApiService)
{
    $validator = Validator::make($request->all(), [
        'idCatTramite'     => 'required|exists:cat_tramites,idCatTramite',
        // 'idGeneral'        => 'required|integer',  logeo 
        'tramiteOrigen'    => 'required|string|max:255',
        // 'folioOficio'      => 'required|string|max:255',
        'folioPreregistro' => 'required|string|max:255',
        'sintesis'         => 'required|string',
        'observaciones'    => 'required|string',
        // 'fechaRecepcion'   => 'required|date',
        'idExpediente'     => 'required|exists:expedientes,idExpediente',
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

        // Datos del usuario
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

        // Subir documento del trámite
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
            // 'folio' => FolioHelper::generarFolio($request->idExpediente),
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
            'tramiteOrigen' => $request->tramiteOrigen,
            // 'folioOficio' => $request->folioOficio,
            'folioOficio' =>$folioTramite,
            'folioPreregistro' => $request->folioPreregistro,
            'sintesis' => $request->sintesis,
            'observaciones' => $request->observaciones,
            // 'fechaRecepcion' => $request->fechaRecepcion,
            'idExpediente' => $request->idExpediente,
        ]);



        // Crear historial de trámite
        $historial = HistoriaEstadoTramite::create([
            'idTramite' => $tramite->idTramite,
            'idUsuario' => $idGeneral,
            // 'descripcion' => 'Trámite creado por el usuario.',
            'idCatHistorialTramite' => 1,
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
    public function show(Tramite $tramite)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Tramite $tramite)
    {
        $validator = Validator::make($request->all(), [
            'idCatTramite' => 'required|exists:cat_tramites,idCatTramite',
            'notificado'   => 'required|boolean',
        ]);

        if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $errorMessage = implode(', ', $errors);
            return response()->json([
                'status' => 422,
                'message' => $errorMessage,
            ], 422);
        }

        // Solo actualiza el campo 'notificado'
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
