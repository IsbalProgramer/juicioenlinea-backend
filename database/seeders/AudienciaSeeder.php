<?php

namespace Database\Seeders;

use App\Models\Audiencia;
use Illuminate\Database\Seeder;

class AudienciaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Audiencia::create([
            'idExpediente' => 1,
            'title' => 'Audiencia Inicial',
            'agenda' => 'Presentación de pruebas',
            'start' => '2025-06-10 09:00:00',
            'end' => '2025-06-10 10:00:00',
            'webLink' => 'https://webex.com/meeting/1',
            'hostEmail' => 'host1@ejemplo.com',
            'id' => 'webexid1',
            'meetingNumber' => '100001',
            'password' => 'pass1',
        ]);
        Audiencia::create([
            'idExpediente' => 1,
            'title' => 'Audiencia de Alegatos',
            'agenda' => 'Alegatos finales',
            'start' => '2025-06-10 11:00:00',
            'end' => '2025-06-10 12:00:00',
            'webLink' => 'https://webex.com/meeting/2',
            'hostEmail' => 'host2@ejemplo.com',
            'id' => 'webexid2',
            'meetingNumber' => '100002',
            'password' => 'pass2',
        ]);
        Audiencia::create([
            'idExpediente' => 1,
            'title' => 'Audiencia de Sentencia',
            'agenda' => 'Lectura de sentencia',
            'start' => '2025-06-10 13:00:00',
            'end' => '2025-06-10 13:30:00',
            'webLink' => 'https://webex.com/meeting/3',
            'hostEmail' => 'host3@ejemplo.com',
            'id' => 'webexid3',
            'meetingNumber' => '100003',
            'password' => 'pass3',
        ]);
        Audiencia::create([
            'idExpediente' => 1,
            'title' => 'Audiencia Complementaria',
            'agenda' => 'Presentación de documentos adicionales',
            'start' => '2025-06-10 15:00:00',
            'end' => '2025-06-10 16:00:00',
            'webLink' => 'https://webex.com/meeting/4',
            'hostEmail' => 'host4@ejemplo.com',
            'id' => 'webexid4',
            'meetingNumber' => '100004',
            'password' => 'pass4',
        ]);
    }
}
