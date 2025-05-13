<?php

namespace Database\Seeders;

use App\Models\Catalogos\CatPartes;
use App\Models\Catalogos\CatTipoPartes;
use Illuminate\Database\Seeder;

class TipoParteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposPartes = [
            ['descripcion' => 'ACTOR', 'plural' => '(ES):', 'tipo' => 'A' ],
            ['descripcion' => 'ACTOR APELANTE APELADO', 'plural' => ':', 'tipo' => 'A1' ],
            ['descripcion' => 'ACTOR APELANTE', 'plural' => ':', 'tipo' => 'AE' ],
            ['descripcion' => 'ACTOR APELADO', 'plural' => ':', 'tipo' => 'AO' ],
            ['descripcion' => 'APELANTE', 'plural' => '(S):', 'tipo' => 'AP' ],
            ['descripcion' => 'ACTO RECLAMADO', 'plural' => ':', 'tipo' => 'AR' ],
            ['descripcion' => 'EN CONTRA', 'plural' => ':', 'tipo' => 'C' ],
            ['descripcion' => 'CESIONARIO', 'plural' => '(S):', 'tipo' => 'CS' ],
            ['descripcion' => 'DEMANDADO', 'plural' => '(S):', 'tipo' => 'D' ],
            ['descripcion' => 'DEMANDO APELANTE APELADO', 'plural' => ':', 'tipo' => 'D1' ],
            ['descripcion' => 'DEMANDADO APELANTE', 'plural' => ':', 'tipo' => 'DE' ],
            ['descripcion' => 'DEFENSOR PÚBLICO', 'plural' => ':', 'tipo' => 'DF' ],
            ['descripcion' => 'DIF MUNICIPAL', 'plural' => ':', 'tipo' => 'DM' ],
            ['descripcion' => 'DEMANDADO APELADO', 'plural' => ':', 'tipo' => 'DO' ],
            ['descripcion' => 'ENDOSATARIO EN PROPIEDAD', 'plural' => ':', 'tipo' => 'E' ],
            ['descripcion' => 'ENDOSATARIO EN GARANTIA', 'plural' => ':', 'tipo' => 'EG' ],
            ['descripcion' => 'EXCEPCIONANTE', 'plural' => ':', 'tipo' => 'ET' ],
            ['descripcion' => 'EXCUSADO', 'plural' => ':', 'tipo' => 'EX' ],
            ['descripcion' => 'FAVORECIDO', 'plural' => '(S):', 'tipo' => 'F' ],
            ['descripcion' => 'MINISTERIO PÚBLICO', 'plural' => ':', 'tipo' => 'MP' ],
            ['descripcion' => 'DENUNCIANTE', 'plural' => '(S):', 'tipo' => 'N' ],
            ['descripcion' => 'APELANTE', 'plural' => '(S):', 'tipo' => 'Ñ' ],
            ['descripcion' => 'ENDOSATARIO EN PROCURACIÓN', 'plural' => ':', 'tipo' => 'O' ],
            ['descripcion' => 'PROMOVENTE', 'plural' => '(S):', 'tipo' => 'P' ],
            ['descripcion' => 'QUEJOSO', 'plural' => '(S):', 'tipo' => 'Q' ],
            ['descripcion' => 'APODERADO', 'plural' => '(S):', 'tipo' => 'R' ],
            ['descripcion' => 'TERCERO', 'plural' => '(S):', 'tipo' => 'T' ],
            ['descripcion' => 'TERCERA PERSONA', 'plural' => ':', 'tipo' => 'TR' ],
            ['descripcion' => 'TRABAJADORA SOCIAL', 'plural' => ':', 'tipo' => 'TS' ],
            ['descripcion' => 'AVAL', 'plural' => '(ES):', 'tipo' => 'V' ],
            ['descripcion' => 'AUTORIZADO FACULTADES AMPLIAS', 'plural' => ':', 'tipo' => 'W' ],
            ['descripcion' => 'EXTINTO', 'plural' => '(S):', 'tipo' => 'X' ],
            ['descripcion' => 'ALBACEA', 'plural' => '(S):', 'tipo' => 'Y' ],
            ['descripcion' => 'PERSONA AUTORIZADA', 'plural' => '(S):', 'tipo' => 'Z' ],
        ];

        foreach ($tiposPartes as $tipoParte) {
            CatTipoPartes::create($tipoParte);
        }
    }
}
