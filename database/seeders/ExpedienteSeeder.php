<?php

namespace Database\Seeders;

use App\Models\Expediente;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;

class ExpedienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear un preregistro
        $preregistro = DB::table('pre_registros')->insertGetId([
            'folioPreregistro' => 'PR-2024-001',
            'idCatMateriaVia' => 1, // Ajusta según tus datos de cat_materia_via
            'sintesis' => 'Sintesis de ejemplo',
            'observaciones' => 'Observaciones de ejemplo',
            'fechaCreada' => now(),
            'idGeneral' => 1001,
            'idUsr' => 'USR001',
            'idCatJuzgado' => 1,
            'idExpediente' => null,
            'fechaResponse' => null,
            'idSecretario' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Crear un expediente usando el preregistro creado
        $expediente = Expediente::create([
            'NumExpediente' => 'EXP-2024-001',
            'idCatJuzgado' => 1,
            'fechaResponse' => now(),
            'idPreregistro' => $preregistro,
            'idSecretario' => 3309,
        ]);

        // Crear un documento relacionado al preregistro y expediente
        DB::table('documentos')->insert([
            'idPreregistro' => $preregistro,
            'idCatTipoDocumento' => 1, // Ajusta según tus tipos de documento
            'nombre' => 'Documento de ejemplo',
            'documento' => 'Contenido del documento',
            'folio' => 'FOLIO-001',
            'idExpediente' => $expediente->idExpediente,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
