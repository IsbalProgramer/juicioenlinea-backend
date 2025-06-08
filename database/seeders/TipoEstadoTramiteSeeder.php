<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatEstadoTramite;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TipoEstadoTramiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        CatEstadoTramite::create ([
            'nombre' => 'Enviado',
            'activo' => 1,
        ]);
          CatEstadoTramite::create ([
            'nombre' => 'Notificado',
            'activo' => 1,
        ]);

    }
}
