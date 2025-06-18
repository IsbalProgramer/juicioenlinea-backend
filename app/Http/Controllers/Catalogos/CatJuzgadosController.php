<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\CatJuzgados as CatalogosCatJuzgados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatJuzgadosController extends Controller
{
      public function index()
{
    $juzgados = CatalogosCatJuzgados::select('idCatJuzgado', 'nombre', 'lugar')->get()->map(function ($juzgado) {
        return [
            'idCatJuzgado' => $juzgado->idCatJuzgado,
            'nombre' => "{$juzgado->nombre} - {$juzgado->lugar}",
        ];
    });

    return response()->json([
        'status' => 200,
        'message' => 'Listado de juzgados',
        'data' => $juzgados
    ]);
}
}
