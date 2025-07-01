<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Audiencia;
use Carbon\Carbon;

class HistorialEstadoAudienciasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Obtiene todas las audiencias existentes
        $audiencias = Audiencia::all();

        foreach ($audiencias as $audiencia) {
            // Asigna un estado aleatorio entre 1 y 3
            $estado = rand(1, 3);

            // Crea el historial de estado
            $audiencia->historialEstados()->create([
                'idCatalogoEstadoAudiencia' => $estado,
                'fechaHora' => Carbon::parse($audiencia->start)->subMinutes(15),
                'observaciones' => 'Estado generado automáticamente por el seeder.',
                'idDocumento' => 1, // o un ID válido existente en tu tabla documentos
            ]);
        }
    }
}
