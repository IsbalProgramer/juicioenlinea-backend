<?php

namespace Database\Seeders;

use App\Models\Catalogos\catGeneros;
use App\Models\Catalogos\catVias;
use App\Models\Catalogos\catMaterias;
use App\Models\Catalogos\catEstadoInicios;
use App\Models\Catalogos\catPartes;
use App\Models\Catalogos\CatTipoDocumento;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Crear materias
        $materias = [
            catMaterias::create(['nombre' => 'Civil', 'activo' => 1]),
            catMaterias::create(['nombre' => 'Familiar', 'activo' => 1]),
        ];

        // Crear vías relacionadas con materias de forma aleatoria
        $vias = [
            'a',
            'b',
            'c',
            'd',
            'f',
        ];

        foreach ($vias as $via) {
            catVias::create([
                'idCatMateria' => $materias[array_rand($materias)]->idCatMateria, // Seleccionar una materia aleatoria
                'nombre' => $via,
                'activo' => 1,
            ]);
        }

        // Crear géneros
        catGeneros::create(['nombre' => 'Masculino', 'activo' => 1]);
        catGeneros::create(['nombre' => 'Femenino', 'activo' => 1]);
        catGeneros::create(['nombre' => 'Otro', 'activo' => 1]);

        // Crear estados iniciales
        catEstadoInicios::create(['nombre' => 'Enviado', 'activo' => 1]);
        catEstadoInicios::create(['nombre' => 'En proceso', 'activo' => 1]);
        catEstadoInicios::create(['nombre' => 'Asignado', 'activo' => 1]);

        // Crear partes
        catPartes::create(['nombre' => 'Demandante', 'activo' => 1]);
        catPartes::create(['nombre' => 'Demandado', 'activo' => 1]);
    
        // Crear tipos de documentos
        $tiposDocumentos = [
            ['idCatTipoDocumento' => 1, 'nombre' => 'Identificación oficial'],
            ['idCatTipoDocumento' => 2, 'nombre' => 'Comprobante de domicilio'],
            ['idCatTipoDocumento' => 3, 'nombre' => 'Acta de nacimiento'],
        ];

        // Insertar los registros normales
        foreach ($tiposDocumentos as $tipo) {
            CatTipoDocumento::create([
                'idCatTipoDocumento' => $tipo['idCatTipoDocumento'],
                'nombre' => $tipo['nombre'],
                'activo' => 1,
            ]);
        }

        // Insertar el registro especial con idCatTipoDocumento = -1 al final
        CatTipoDocumento::create([
            'idCatTipoDocumento' => -1,
            'nombre' => 'OTRO',
            'activo' => 1,
        ]);
    }
}

