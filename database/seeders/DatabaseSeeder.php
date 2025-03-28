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
            'nombre' => 'Creado',
            'activo' => 1,

        ]);
        catEstadoInicios::create([
            'nombre' => 'Proceso',
            'activo' => 1,

        ]);
        catEstadoInicios::create([
            'nombre' => 'Expediente asignado',
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

    
        $faker = Faker::create();  // Crear una instancia de Faker

        // Insertar 10 usuarios aleatorios
        for ($i = 0; $i < 10; $i++) {
            DB::table('users')->insert([
                'name' => $faker->name, // Generar nombre aleatorio
                'email' => $faker->unique()->safeEmail, // Generar correo electrónico único
                'password' => Hash::make('contraseñaSegura' . $i), // Contraseña aleatoria
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        
        


    }
}

