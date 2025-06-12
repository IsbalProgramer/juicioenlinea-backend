<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatJuzgados;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoJuzgadoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //

        $juzgados = [
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Familiar Segundo', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Familiar Tercero', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Familiar Cuarto', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Tuxtepec'],
            ['nombre' => 'Juzgado Familiar Segundo', 'lugar' => 'Huajuapan'],
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Salina Cruz'],
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Puerto Escondido'],
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Tehuantepec'],
            ['nombre' => 'Juzgado Familiar Primero', 'lugar' => 'Juchitán'],

            ['nombre' => 'Juzgado Civil Primero', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Civil Segundo', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Civil Tercero', 'lugar' => 'Centro'],
            ['nombre' => 'Juzgado Civil Primero', 'lugar' => 'Tuxtepec'],
            ['nombre' => 'Juzgado Civil Segundo', 'lugar' => 'Huajuapan'],
            ['nombre' => 'Juzgado Civil Primero', 'lugar' => 'Salina Cruz'],
            ['nombre' => 'Juzgado Civil Segundo', 'lugar' => 'Juchitán'],
            ['nombre' => 'Juzgado Civil Primero', 'lugar' => 'Puerto Escondido'],
            ['nombre' => 'Juzgado Civil Primero', 'lugar' => 'Nochixtlán'],
        ];
        foreach ($juzgados as  $juzgado) {
            CatJuzgados::create($juzgado);
        }
    }
}
