<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoSecretarioJuzgado extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $secretarios = [
            ['idUsrSecretario' => 4714, 'idGeneralSecretario' => 3309],
            ['idUsrSecretario' => 2234, 'idGeneralSecretario' => 3344],
        ];

        // Obtén los IDs existentes de la tabla OPV_CatJuzgados
        $juzgados = DB::table('OPV_CatJuzgados')->pluck('IdCatJuzgado');

        $data = [];
        foreach ($juzgados as $idCatJuzgado) {
            $sec = $secretarios[array_rand($secretarios)];
            $data[] = [
                'idUsrSecretario' => $sec['idUsrSecretario'],
                'idGeneralSecretario' => $sec['idGeneralSecretario'],
                'idCatJuzgado' => $idCatJuzgado,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('cat_secretario_juzgado')->insert($data);
    }
}
