<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use Illuminate\Http\Request;
use App\Services\MeetingService;

class AudienciaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $audiencias = Audiencia::with(['expediente', 'ultimoEstado.catalogoEstadoAudiencia'])->get();

            $data = $audiencias->map(function ($audiencia) {
                $arr = $audiencia->toArray();

                // Transformar el último estado para incluir la descripción del catálogo directamente
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

                unset($arr['ultimoEstado']); // Elimina el original

                return $arr;
            });

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Listado de audiencias',
                'data' => $data
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
        $validated = $request->validate([
            'idExpediente' => 'required|integer|exists:expedientes,idExpediente',
            'title' => 'required|string|max:255',
            'agenda' => 'nullable|string|max:255',
            'start' => 'required|date_format:Y-m-d H:i:s',
            'end' => 'required|date_format:Y-m-d H:i:s|after:start',
            'timezone' => 'required|string|max:64',
            'excludePassword' => 'required|boolean',
            'reminderTime' => 'required|integer|min:0',
            'unlockedMeetingJoinSecurity' => 'required|string|max:64',
            'sendEmail' => 'required|boolean',
            'hostEmail' => 'required|email|max:255',
            'invitees' => 'required|array|min:1',
            'invitees.*.email' => 'required|email|max:255',
            'invitees.*.displayName' => 'required|string|max:255',
            'invitees.*.coHost' => 'sometimes|boolean',
        ]);

        try {
            $webexToken = env('WEBEX_TOKEN');

            // Solo pasa los datos validados, MeetingService se encarga de los defaults
            $webexResponse = $meetingService->crearReunion($webexToken, $validated);

            if (!isset($webexResponse['webLink'])) {
                return response()->json($webexResponse, 500);
            }

            $webLink = $webexResponse['webLink'];
            $idMeeting = $webexResponse['id'] ?? null;
            $meetingNumber = $webexResponse['meetingNumber'] ?? null;
            $password = $webexResponse['password'] ?? null;

            $audiencia = Audiencia::create([
                'idExpediente' => $validated['idExpediente'],
                'title' => $validated['title'],
                'agenda' => $validated['agenda'] ?? null,
                'start' => $validated['start'],
                'end' => $validated['end'],
                'webLink' => $webLink,
                'hostEmail' => $validated['hostEmail'],
                'id' => $idMeeting,
                'meetingNumber' => $meetingNumber,
                'password' => $password,
            ]);

            foreach ($validated['invitees'] as $invitado) {
                $audiencia->invitados()->create([
                    'email' => $invitado['email'],
                    'displayName' => $invitado['displayName'],
                    'coHost' => isset($invitado['coHost']) && $invitado['coHost'] ? 'true' : 'false',
                ]);
            }
            // Insertar historial de estado de la audiencia
            $audiencia->historialEstados()->create([
                'idCatalogoEstadoAudiencia' => 1,
                'fechaHora' => now(),
                'observaciones' => 'Audiencia creada y activa',
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
    public function show(Audiencia $audiencia)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Audiencia $audiencia)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Audiencia $audiencia)
    {
        //
    }
}
