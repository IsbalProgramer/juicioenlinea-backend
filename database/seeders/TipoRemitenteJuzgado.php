<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoRemitenteJuzgado extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        for ($i = 1; $i <= 40; $i++) {
            // Asignamos a cada remitente un juzgado aleatorio entre 1 y 19
            DB::table('cat_remitente_juzgados')->insert([
                'idCatJuzgado' => rand(1, 19),
                'idCatRemitente' => $i,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
