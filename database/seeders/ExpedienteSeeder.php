<?php

namespace Database\Seeders;

use App\Models\Expediente;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

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
