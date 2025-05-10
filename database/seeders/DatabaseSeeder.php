<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatGeneros;
use App\Models\Catalogos\CatMaterias;
use App\Models\Catalogos\CatEstadoInicios;
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
            ['descripcion' => 'CIVIL', 'claveMateria' => 'C' ],
            ['descripcion' => 'FAMILIAR', 'claveMateria' => 'F' ],
            ['descripcion' => 'MERCANTIL', 'claveMateria' => 'M' ],
            ['descripcion' => 'MERCANTIL ORAL', 'claveMateria' => 'O' ],
            ['descripcion' => 'LABORAL', 'claveMateria' => 'L' ],
            ['descripcion' => 'FAMILIAR PATERNIDAD', 'claveMateria' => 'PT' ],
        ];
        foreach ($materias as $materia) {
            CatMaterias::create($materia);
        }

        // Crear géneros
        CatGeneros::create(['descripcion' => 'HOMBRE' ]);
        CatGeneros::create(['descripcion' => 'MUJER' ]);
        CatGeneros::create(['descripcion' => 'OTRO' ]);

        // Crear estados iniciales
        CatEstadoInicios::create(['descripcion' => 'Enviado' ]);
        CatEstadoInicios::create(['descripcion' => 'En proceso' ]);
        CatEstadoInicios::create(['descripcion' => 'Asignado' ]);

        $this->call(TipoViaSeeder::class);
        $this->call(TipoMateriaViaSeeder::class);
        $this->call(TipoParteSeeder::class);
        $this->call(TipoDocumentoSeeder::class);



  
    }
}

