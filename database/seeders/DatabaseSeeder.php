<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatEstadoAudiencia;
use App\Models\Catalogos\CatGeneros;
use App\Models\Catalogos\CatMaterias;
use App\Models\Catalogos\CatEstadoInicios;
use App\Models\Catalogos\CatSexos;
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
            ['descripcion' => 'CIVIL', 'claveMateria' => 'C', 'activo' => 1 ],
            ['descripcion' => 'FAMILIAR', 'claveMateria' => 'F', 'activo' => 1 ],
            ['descripcion' => 'MERCANTIL', 'claveMateria' => 'M', 'activo' => 0 ],
            ['descripcion' => 'MERCANTIL ORAL', 'claveMateria' => 'O', 'activo' => 0 ],
            ['descripcion' => 'LABORAL', 'claveMateria' => 'L', 'activo' => 0 ],
            ['descripcion' => 'FAMILIAR PATERNIDAD', 'claveMateria' => 'PT', 'activo' => 1 ],
        ];
        foreach ($materias as $materia) {
            CatMaterias::create($materia);
        }

        // Crear géneros
        CatSexos::create(['descripcion' => 'HOMBRE' ]);
        CatSexos::create(['descripcion' => 'MUJER' ]);
        CatSexos::create(['descripcion' => 'OTRO' ]);

        // Crear estados iniciales
        CatEstadoInicios::create(['descripcion' => 'Enviado' ]);
        CatEstadoInicios::create(['descripcion' => 'Asignado' ]);
        CatEstadoInicios::create(['descripcion' => 'Finalizado' ]);

        // Crear estados para aduiencias
        CatEstadoAudiencia::create(['descripcion' => 'Activa' ]);
        CatEstadoAudiencia::create(['descripcion' => 'Finalizada' ]);
        CatEstadoAudiencia::create(['descripcion' => 'Reprogramada' ]);
        CatEstadoAudiencia::create(['descripcion' => 'Cancelada' ]);

        $this->call(TipoViaSeeder::class);
        $this->call(TipoMateriaViaSeeder::class);
        $this->call(TipoParteSeeder::class);
        $this->call(TipoDocumentoSeeder::class);
        $this->call(RequerimientosSeeder::class);

    }
}

