<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExpedienteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Expediente::create([
            'sintesis' => 'Primer expediente creado',
            'folio_preregistro' => 'ABC-TEST',
            'activo' => 1,

        ]);
    }
}
