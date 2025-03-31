<?php

namespace Database\Seeders;


use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\Catalogos\CatEstadoRequerimiento;


class RequerimientosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Catalogo de requerimientos
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento creado',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento subido',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento aceptado',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento denegado',
            'activo' => 1,
        ]);

        
    }
}
