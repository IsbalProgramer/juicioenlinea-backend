<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\CatMateriaVia;
use App\Models\Catalogos\CatTipoVias;
use Illuminate\Http\Request;

class CatViasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $catVias = CatTipoVias::all();
            return response()->json([
                'status' => 200,
                'message' => "Catálogos de vias",
                'data' => $catVias
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener el catálogo de vias',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $idCatMateria)
    {
        try {
            // Obtener los idCatTipoVia relacionados a la materia
            $vias = CatMateriaVia::where('idCatMateria', $idCatMateria)
                ->with('catVia') 
                ->get() 
                ->pluck('catVia') // Extrae solo las vías relacionadas
                ->filter(); // Elimina valores nulos

            return response()->json([
                'status' => 200,
                'message' => "Vías relacionadas con la materia $idCatMateria",
                'data' => $vias->values()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener las vías relacionadas con la materia',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
