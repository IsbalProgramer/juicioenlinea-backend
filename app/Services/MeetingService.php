<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class MeetingService
{
    protected $baseUrl = 'https://webexapis.com/v1/meetings';

    /**
     * Crea una reunión en Webex.
     *
     * @param string $token
     * @param array $data
     * @return array|null
     */
    public function crearReunion(string $token, array $data)
    {
        // Valores por defecto
        $defaults = [
            'enabledAutoRecordMeeting' => true,
            'allowAnyUserToBeCoHost' => false,
            'enabledVisualWatermark' => true, //marca de agua video
            'visualWatermarkOpacity' => 20,
            'audioConnectionOptions' => ['audioConnectionType' => 'VoIP'],
            'allowAuthenticatedDevices' => true,
        ];

        // Mezclar datos recibidos con los valores por defecto
        $payload = array_merge($defaults, $data);

        $payload = [
            'title' => $payload['title'],
            'agenda' => $payload['agenda'] ?? null,
            'start' => $payload['start'],
            'end' => $payload['end'],
            'timezone' => $payload['timezone'],
            'enabledAutoRecordMeeting' => $payload['enabledAutoRecordMeeting'],
            'allowAnyUserToBeCoHost' => $payload['allowAnyUserToBeCoHost'],
            'excludePassword' => $payload['excludePassword'],
            'reminderTime' => $payload['reminderTime'],
            'unlockedMeetingJoinSecurity' => $payload['unlockedMeetingJoinSecurity'],
            'sendEmail' => $payload['sendEmail'],
            'hostEmail' => $payload['hostEmail'],
            'enabledVisualWatermark' => $payload['enabledVisualWatermark'],
            'visualWatermarkOpacity' => $payload['visualWatermarkOpacity'],
            'audioConnectionOptions' => $payload['audioConnectionOptions'],
            'allowAuthenticatedDevices' => $payload['allowAuthenticatedDevices'],
            'invitees' => $payload['invitees'] ?? [],
        ];

        // Eliminar valores nulos
        $payload = array_filter($payload, function ($v) {
            return !is_null($v);
        });

        $response = Http::withToken($token)
            ->acceptJson()
            ->post($this->baseUrl, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'success' => false,
            'status' => $response->status(),
            'message' => $response->json()['message'] ?? 'Error al crear la reunión',
            'errors' => $response->json()['errors'] ?? null,
        ];
    }

    public function actualizarReunion(string $token, string $meetingId, array $data)
    {
        // Solo los campos permitidos por la API de Webex
        $payload = [
            'title' => $data['title'] ?? null,
            'agenda' => $data['agenda'] ?? null,
            'start' => $data['start'] ?? null,
            'end' => $data['end'] ?? null,
        ];

        // Eliminar valores nulos
        $payload = array_filter($payload, function ($v) {
            return !is_null($v);
        });

        $url = $this->baseUrl . '/' . $meetingId;

        $response = Http::withToken($token)
            ->acceptJson()
            ->patch($url, $payload);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'success' => false,
            'status' => $response->status(),
            'message' => $response->json()['message'] ?? 'Error al actualizar la reunión',
            'errors' => $response->json()['errors'] ?? null,
        ];
    }
}
