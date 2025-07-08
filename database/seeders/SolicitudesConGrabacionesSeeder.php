<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audiencia;
use App\Models\Grabaciones;
use App\Models\Solicitud;
use App\Models\HistorialEstadoSolicitud;
use App\Models\Solicitudes;
use Carbon\Carbon;

class SolicitudesConGrabacionesSeeder extends Seeder
{
    public function run(): void
    {
        // Trae todas las audiencias
        $audiencias = Audiencia::all();

        foreach ($audiencias as $audiencia) {
            // Crear una grabación por cada audiencia
            $grabacion = Grabaciones::create([
                'idAudiencia'       => $audiencia->idAudiencia,
                'id'                => 'grab_' . $audiencia->idAudiencia,
                'topic'             => 'Grabación de ' . $audiencia->title,
                'meetingSeriesId'   => 'series_' . $audiencia->idAudiencia,
                'timeRecorded'      => Carbon::now()->toDateTimeString(),
                'downloadUrl'       => 'https://grabaciones.ejemplo.com/' . $audiencia->idAudiencia . '/download',
                'playbackUrl'       => 'https://grabaciones.ejemplo.com/' . $audiencia->idAudiencia . '/play',
                'password'          => 'pass' . rand(1000, 9999),
                'durationSeconds'   => rand(600, 3600),
            ]);

            // Crear una solicitud por cada grabación
            $solicitud = Solicitudes::create([
                'idGrabacion'   => $grabacion->idGrabacion,
                'idGeneral'     => 30057,
                'observaciones' => 'Solicitud creada automáticamente por el Seeder',
            ]);

            // Crear historial de estado para la solicitud
            HistorialEstadoSolicitud::create([
                'idSolicitud'                => $solicitud->idSolicitud,
                'idCatalogoEstadoSolicitud'  => 1, // Por ejemplo, 1=Pendiente
                'fechaEstado'                => Carbon::now(),
                'idDocumento'                => null, // O pon un idDocumento válido si quieres
            ]);
        }

        $this->command->info('Se crearon grabaciones, solicitudes e historiales para ' . count($audiencias) . ' audiencias.');
    }
}
