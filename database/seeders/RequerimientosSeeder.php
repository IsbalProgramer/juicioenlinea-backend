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
            'nombre' => 'Requerimiento Creado',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Expirado',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Entregado',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Actualizado',
            'activo' => 1,
        ]);

        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Eliminado',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Admitido',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Descartado',
            'activo' => 1,
        ]);

        
    }
}
