<?php

namespace App\Http\Controllers;

use App\Models\Audiencia;
use App\Models\Documento;
use Illuminate\Http\Request;
use App\Services\MeetingService;
use App\Services\NasApiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

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
            'invitees.*.coHost' => 'sometimes|boolean|nullable',
            'invitees.*.idCatSexo' => 'required|integer',
            'invitees.*.idCatTipoParte' => 'required|integer',
            'invitees.*.idUsr' => 'nullable|integer',
            'invitees.*.direccion' => 'required|string|max:255',
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

        // Normalizar los invitados: siempre agregar el email principal y, si existe, el correo alternativo
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

            $audiencia = Audiencia::create([
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
    public function show($idAudiencia)
    {
        try {
            $audiencia = Audiencia::with([
                'invitados.catSexo',
                'invitados.catTipoParte',
                'expediente',
                'ultimoEstado.catalogoEstadoAudiencia'
            ])->findOrFail($idAudiencia);

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
                ];
            } else {
                $arr['ultimo_estado'] = null;
            }
            unset($arr['ultimoEstado']);

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
        $audiencia = Audiencia::findOrFail($idAudiencia);

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

        // Actualizar en Webex
        $webexResponse = $meetingService->actualizarReunion($webexToken, $meetingId, $validated);
        Log::info('Webex response:', $webexResponse);

        if (!isset($webexResponse['webLink'])) {
            return response()->json($webexResponse);
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

    public function actualizarEstado(Request $request, $idAudiencia, NasApiService $nasApiService)
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

        try {
            $audiencia = Audiencia::findOrFail($idAudiencia);

            // Subir documento al NAS
            $file = $request->file('documento');
            $idCatTipoDocumento = $request->input('idCatTipoDocumento');
            // Extraer año y número del expediente
            list($numeroExpediente, $anioExpediente) = explode('/', $audiencia->idExpediente);

            $ruta = "PERICIALES/AUDIENCIAS/{$anioExpediente}/{$numeroExpediente}";
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

            // Observación personalizada
            $observacion = $request->input('observaciones', 'Estado de la audiencia actualizado a 2');

            // Crear historial de estado con idDocumento
            $audiencia->historialEstados()->create([
                'idCatalogoEstadoAudiencia' => 2,
                'fechaHora' => now(),
                'observaciones' => $observacion,
                'idDocumento' => $documento->idDocumento,
            ]);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Estado de la audiencia y documento actualizados correctamente',
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

    public function disponibilidad(Request $request)
    {
        $fecha = $request->query('fecha'); // formato: YYYY-MM-DD
        $idAudiencia = $request->query('idAudiencia'); // opcional

        if (!$fecha) {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar el parámetro fecha en formato YYYY-MM-DD'
            ], 400);
        }

        // Rango de 07:00 a 22:00
        $inicioDia = Carbon::parse($fecha . ' 07:00:00');
        $finDia = Carbon::parse($fecha . ' 22:00:00');

        $audiencias = Audiencia::whereDate('start', $fecha)
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
        foreach ($libres as $rango) {
            $slotStart = $rango['start']->copy();
            while ($slotStart->lt($rango['end'])) {
                $slotEnd = $slotStart->copy()->addMinutes(30);
                if ($slotEnd->gt($rango['end'])) {
                    $slotEnd = $rango['end']->copy();
                }
                if ($slotStart->lt($slotEnd)) {
                    $disponibles[] = $slotStart->format('H:i');
                }
                $slotStart = $slotEnd->copy();
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

        if (!$fecha || !$horaInicio) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'Debes enviar los parámetros fecha (YYYY-MM-DD) y start (HH:mm)'
            ], 400);
        }

        // Rango de 07:00 a 22:00
        $inicioDia = Carbon::parse($fecha . ' 07:00:00');
        $finDia = Carbon::parse($fecha . ' 22:00:00');

        $audiencias = Audiencia::whereDate('start', $fecha)
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
