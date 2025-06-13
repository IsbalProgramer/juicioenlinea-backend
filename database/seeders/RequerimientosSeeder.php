<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Catalogos\CatEstadoRequerimiento;
use App\Models\AbogadoExpediente;

class RequerimientosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Catalogo de requerimientos
        CatEstadoRequerimiento::create([
            'nombre' => 'Pendiente',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Expirado',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Entregado',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Aceptado',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Rechazado',
            'activo' => 1,
        ]);
       
    }
}
