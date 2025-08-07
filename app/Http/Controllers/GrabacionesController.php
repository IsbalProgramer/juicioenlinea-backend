<?php

namespace App\Http\Controllers;

use App\Models\Grabaciones;
use Illuminate\Http\Request;
use App\Models\Audiencia;
use App\Services\MeetingService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class GrabacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store($idAudiencia, MeetingService $meetingService)
    {
        $audiencia = Audiencia::with('historialEstados')->findOrFail($idAudiencia);

        // Buscar el historialEstado finalizado (idCatalogoEstadoAudiencia = 2)
        $finalizada = $audiencia->historialEstados()
            ->where('idCatalogoEstadoAudiencia', 2)
            ->orderByDesc('fechaHora')
            ->first();

        if (!$finalizada) {
            return response()->json([
                'success' => false,
                'message' => 'La audiencia no ha sido finalizada.',
            ], 400);
        }

        // // Solo continuar si ya pasó al menos 1 hora desde que finalizó
        // if (Carbon::parse($finalizada->fechaHora)->addHour()->isFuture()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Debe esperar al menos 1 hora después de finalizar la audiencia.',
        //     ], 400);
        // }

        // Rango de fechas: TODO el día de hoy
        $fechaAudiencia = Carbon::parse($audiencia->start);
        $from = $fechaAudiencia->copy()->subDay()->format('Y-m-d') . 'T00:00:00-06:00';
        $to = now()->format('Y-m-d') . 'T23:59:59-06:00';
        
        $meetingSeriesId = $audiencia->id; // el campo 'id' de tu modelo Audiencia
        Log::info("Obteniendo grabaciones para audiencia: {$audiencia->idAudiencia}, desde: $from, hasta: $to, meetingSeriesId: $meetingSeriesId");

        $token = env('WEBEX_TOKEN');
        $grabacionesResponse = $meetingService->obtenerGrabaciones($token, $from, $to);

        Log::info("respuesta grabaciones: ", $grabacionesResponse);

        if (!$grabacionesResponse['success']) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudieron obtener las grabaciones del servicio.',
                'error' => $grabacionesResponse['message'] ?? null,
            ], 500);
        }

        $items = $grabacionesResponse['data'];
        $grabacionesGuardadas = [];

        foreach ($items as $item) {
            if (isset($item['meetingSeriesId']) && $item['meetingSeriesId'] === $meetingSeriesId) {
                // Guarda solo los campos definidos en tu modelo
                $grabacion = Grabaciones::updateOrCreate(
                    ['id' => $item['id']], // Evita duplicados por id de grabación Webex
                    [
                        'idAudiencia'      => $audiencia->idAudiencia,
                        'id'               => $item['id'],
                        'meetingSeriesId'  => $item['meetingSeriesId'],
                        'topic'            => $item['topic'] ?? null,
                        'timeRecorded'     => $item['timeRecorded'] ?? null,
                        'downloadUrl'      => $item['downloadUrl'] ?? null,
                        'playbackUrl'      => $item['playbackUrl'] ?? null,
                        'password'         => $item['password'] ?? null,
                        'durationSeconds'  => $item['durationSeconds'] ?? null,
                    ]
                );
                $grabacionesGuardadas[] = $grabacion;
            }
        }
        
        if (empty($grabacionesGuardadas)) {
            return response()->json([
                'success' => true,
                'message' => 'No hay grabaciones disponibles para esta audiencia.',
                'data' => [],
            ], 200);
        }
        return response()->json([
            'success' => true,
            'message' => 'Grabaciones guardadas correctamente',
            'data' => $grabacionesGuardadas,
        ]);
    }
    /**
     * Display the specified resource.
     */
    public function show(Grabaciones $grabaciones) {}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Grabaciones $grabaciones)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Grabaciones $grabaciones)
    {
        //
    }
}
