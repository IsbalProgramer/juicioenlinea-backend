<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoSecretarioJuzgado extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cat_secretario_juzgado')->insert([
            [
                'idUsr' => 4808,
                'idGeneral' => 3317,
                'idCatJuzgado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsr' => 4809,
                'idGeneral' => 3318,
                'idCatJuzgado' => 2,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'idUsr' => 4810,
                'idGeneral' => 3319,
                'idCatJuzgado' => 3,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
              [
                'idUsr' => 4714,
                'idGeneral' => 3309,
                'idCatJuzgado' => 4,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'idUsr' => 4811,
                'idGeneral' => 3320,
                'idCatJuzgado' => 5,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'idUsr' => 4812,
                'idGeneral' => 3321,
                'idCatJuzgado' => 6,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'idUsr' => 4813,
                'idGeneral' => 3322,
                'idCatJuzgado' => 7,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'idUsr' => 4814,
                'idGeneral' => 3323,
                'idCatJuzgado' => 8,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'idUsr' => 4815,
                'idGeneral' => 3324,
                'idCatJuzgado' => 9,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],
            [
                'idUsr' => 4816,
                'idGeneral' => 3325,
                'idCatJuzgado' => 10,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'idUsr' => 4804,
                'idGeneral' => 3313,
                'idCatJuzgado' => 11,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],

            [
                'idUsr' => 4805,
                'idGeneral' => 3314,
                'idCatJuzgado' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsr' => 4714,
                'idGeneral' => 3309,
                'idCatJuzgado' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsr' => 4806,
                'idGeneral' => 3315,
                'idCatJuzgado' => 14,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],

            [
                'idUsr' => 4807,
                'idGeneral' => 3316,
                'idCatJuzgado' => 15,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ]);
    }
}
