<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatTramites;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoTramiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //Catalogo de tipo Tramite
        CatTramites::create([
            'nombre' => 'Oficio',
            'activo' => 1,
        ]);
        //Catalogo de tipo Tramite
        CatTramites::create([
            'nombre' => 'Promoción',
            'activo' => 1,
        ]);
        //Catalogo de tipo Tramite
        CatTramites::create([
            'nombre' => 'Amparo',
            'activo' => 1,
        ]);
        //Catalogo de tipo Tramite
        CatTramites::create([
            'nombre' => 'Exhorto',
            'activo' => 1,
        ]);

    }
}
