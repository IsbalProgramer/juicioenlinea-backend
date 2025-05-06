<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Catalogos\catGeneros;
use App\Models\Catalogos\catVias;
use App\Models\Catalogos\catMaterias;
use App\Models\Catalogos\catEstadoInicios;
use App\Models\Catalogos\catPartes;
use Illuminate\Process\FakeProcessResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;




// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        catGeneros::create([
            'nombre' => 'Masculino',
            'activo' => 1,

        ]);
        catGeneros::create([
            'nombre' => 'Femenino',
            'activo' => 1,

        ]);
        catVias::create([
            'nombre' => 'Juicio en línea',
            'activo' => 1,

        ]);
        catVias::create([
            'nombre' => 'Juicio presencial',
            'activo' => 1,

        ]);
        catMaterias::create([
            'nombre' => 'Civil',
            'activo' => 1,

        ]);
        catMaterias::create([
            'nombre' => 'Familiar',
            'activo' => 1,

        ]);
        catEstadoInicios::create([
            'nombre' => 'Enviado',
            'activo' => 1,

        ]);
        catEstadoInicios::create([
            'nombre' => 'En proceso',
            'activo' => 1,

        ]);
        catEstadoInicios::create([
            'nombre' => 'Asignado',
            'activo' => 1,

        ]);
        
        catPartes::create([
            'nombre' => 'Demandadante',
            'activo' => 1,

        ]);
        catPartes::create([
            'nombre' => 'Demandado',
            'activo' => 1,

        ]);


  


    }
}

