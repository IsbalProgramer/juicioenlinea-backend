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
                'idUsrSecretario' => 4808,
                'idGeneralSecretario' => 3317,
                'idCatJuzgado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsrSecretario' => 4809,
                'idGeneralSecretario' => 3318,
                'idCatJuzgado' => 2,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'idUsrSecretario' => 4810,
                'idGeneralSecretario' => 3319,
                'idCatJuzgado' => 3,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
              [
                'idUsrSecretario' => 4714,
                'idGeneralSecretario' => 3309,
                'idCatJuzgado' => 4,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
            [
                'idUsrSecretario' => 4811,
                'idGeneralSecretario' => 3320,
                'idCatJuzgado' => 5,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'idUsrSecretario' => 4812,
                'idGeneralSecretario' => 3321,
                'idCatJuzgado' => 6,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'idUsrSecretario' => 4813,
                'idGeneralSecretario' => 3322,
                'idCatJuzgado' => 7,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'idUsrSecretario' => 4814,
                'idGeneralSecretario' => 3323,
                'idCatJuzgado' => 8,
                'created_at' => now()->subDays(6),
                'updated_at' => now()->subDays(6),
            ],
            [
                'idUsrSecretario' => 4815,
                'idGeneralSecretario' => 3324,
                'idCatJuzgado' => 9,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],
            [
                'idUsrSecretario' => 4816,
                'idGeneralSecretario' => 3325,
                'idCatJuzgado' => 10,
                'created_at' => now()->subDays(8),
                'updated_at' => now()->subDays(8),
            ],
            [
                'idUsrSecretario' => 4804,
                'idGeneralSecretario' => 3313,
                'idCatJuzgado' => 11,
                'created_at' => now()->subDays(7),
                'updated_at' => now()->subDays(7),
            ],

            [
                'idUsrSecretario' => 4805,
                'idGeneralSecretario' => 3314,
                'idCatJuzgado' => 12,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsrSecretario' => 4714,
                'idGeneralSecretario' => 3309,
                'idCatJuzgado' => 13,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'idUsrSecretario' => 4806,
                'idGeneralSecretario' => 3315,
                'idCatJuzgado' => 14,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],

            [
                'idUsrSecretario' => 4807,
                'idGeneralSecretario' => 3316,
                'idCatJuzgado' => 15,
                'created_at' => now()->subDays(1),
                'updated_at' => now()->subDays(1),
            ],
        ]);
    }
}
