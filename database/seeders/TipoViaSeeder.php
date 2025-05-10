<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoViaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposVia = [
            ['descripcion' => 'ACTO PREJUDICIAL', 'modelo' => 1 ],
            ['descripcion' => 'CONCURSO VOLUNTARIO', 'modelo' => 1 ],
            ['descripcion' => 'CONTROVERSIAS', 'modelo' => 1 ],
            ['descripcion' => 'DIVORCIO VOLUNTARIO', 'modelo' => 2 ],
            ['descripcion' => 'EJECUTIVA CIVIL', 'modelo' => 1 ],
            ['descripcion' => 'EJECUTIVA MERCANTIL', 'modelo' => 1 ],
            ['descripcion' => 'JUICIO CONCURSAL', 'modelo' => 1 ],
            ['descripcion' => 'JUICIO ESPECIAL MERCANTIL AUT. DE VENTA', 'modelo' => 1 ],
            ['descripcion' => 'JUICIO SUCESORIO INTESTAMENTARIO', 'modelo' => 3 ],
            ['descripcion' => 'JUICIO SUCESORIO TESTAMENTARIO', 'modelo' => 3 ],
            ['descripcion' => 'JURISDICCION VOLUNTARIA', 'modelo' => 2 ],
            ['descripcion' => 'MEDIOS PREPARATORIOS', 'modelo' => 2 ],
            ['descripcion' => 'ORDINARIA CIVIL', 'modelo' => 1 ],
            ['descripcion' => 'ORDINARIA MERCANTIL', 'modelo' => 1 ],
            ['descripcion' => 'SUMARIA CIVIL', 'modelo' => 1 ],
            ['descripcion' => 'SUMARIA HIPOTECARIA', 'modelo' => 1 ],
            ['descripcion' => 'VIA DE APREMIO', 'modelo' => 2 ],
            ['descripcion' => 'VIA ESPECIAL DE DESAHUCIO', 'modelo' => 1 ],
            ['descripcion' => 'VIA INCIDENTAL', 'modelo' => 2 ],
            ['descripcion' => 'COMPETENCIA POR INHIBITORIA', 'modelo' => 1 ],
            ['descripcion' => 'JUICIO ESPECIAL MERCANTIL', 'modelo' => 1 ],
            ['descripcion' => 'SUMARIO ESPECIAL DE DESAHUCIO', 'modelo' => 1 ],
            ['descripcion' => 'PROVIDENCIAS PRECAUTORIAS', 'modelo' => 2 ],
            ['descripcion' => 'COMPARECENCIA EN LA SEPAR. DE CONYUGES', 'modelo' => 1 ],
            ['descripcion' => 'APELACION MUNICIPAL', 'modelo' => 2 ],
            ['descripcion' => 'COMPARECENCIA', 'modelo' => 1 ],
            ['descripcion' => 'CONSIGNACION DE PAGO', 'modelo' => 2 ],
            ['descripcion' => 'DILIGENCIAS DE CONSIGNACION', 'modelo' => 2 ],
            ['descripcion' => 'VIA ESPECIAL DE RECTIFICACION DE ACTA', 'modelo' => 1 ],
            ['descripcion' => 'SUSPENSION PROVISIONAL DE OBRA NUEVA', 'modelo' => 2 ],
            ['descripcion' => 'JUICIO PRENDARIO', 'modelo' => 1 ],
            ['descripcion' => 'ORDINARIA ORAL MERCANTIL', 'modelo' => 1 ],
            ['descripcion' => 'NO ESPECIFICA VIA', 'modelo' => 1 ],
            ['descripcion' => 'DIVORCIO INCAUSADO', 'modelo' => 2 ],
            ['descripcion' => 'JUICIO EJECUTIVO MERCANTIL ORAL', 'modelo' => 1 ],
            ['descripcion' => 'PROVIDENCIAS PRECAUTORIAS MERCANTIL ORAL', 'modelo' => 1 ],
            ['descripcion' => 'MEDIOS PREPARATORIOS A JUICIO ORAL MERCANTIL', 'modelo' => 1 ],
            ['descripcion' => 'VIA ESPECIAL DE ACCION DE EXTINCION DE DOMINIO', 'modelo' => 2 ],
            ['descripcion' => 'ORDENES DE PROTECCION', 'modelo' => 2 ],
            ['descripcion' => 'MEDIOS PREPARATORIOS', 'modelo' => 3 ],
            ['descripcion' => 'CONTROVERSIAS (RECONOCIMIENTO DE PATERNIDAD)', 'modelo' => 6 ],
            ['descripcion' => 'CONTROVERSIAS (CONTRADICCIÓN DE PATERNIDAD)', 'modelo' => 6 ],
            ['descripcion' => 'SOLICITUD DE ORDEN DE PROTECCIÓN', 'modelo' => 2 ],
        ];

        DB::table('cat_tipo_vias')->insert($tiposVia);
    }
}
