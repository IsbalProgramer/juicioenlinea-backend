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
            'nombre' => 'Requerimiento Admitido',
            'activo' => 1,
        ]);
        CatEstadoRequerimiento::create([
            'nombre' => 'Requerimiento Descartado',
            'activo' => 1,
        ]);

        AbogadoExpediente::create([
            'idExpediente' => '0001/2020',
            'idAbogado' => 30057,
            'activo' => 1,
        ]);

        AbogadoExpediente::create([
            'idExpediente' => '0001/2020',
            'idAbogado' => 30058,
            'activo' => 1,
        ]);
        
        AbogadoExpediente::create([
            'idExpediente' => '0001/2020',
            'idAbogado' => 30059,
            'activo' => 1,
        ]);
        AbogadoExpediente::create([
            'idExpediente' => '0002/2020',
            'idAbogado' => 30060,
            'activo' => 1,
        ]);

        AbogadoExpediente::create([
            'idExpediente' => '0003/2020',
            'idAbogado' => 30057,
            'activo' => 1,
        ]);
        AbogadoExpediente::create([
            'idExpediente' => '0002/2020',
            'idAbogado' => 30059,
            'activo' => 1,
        ]);
    }
}
