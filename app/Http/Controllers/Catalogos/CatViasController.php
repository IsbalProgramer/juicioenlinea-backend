<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\CatVias;
use Illuminate\Http\Request;

class CatViasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $catVias = CatVias::all();
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
            // Obtener las vías relacionadas con la materia específica
            $catVias = CatVias::where('idCatMateria', $idCatMateria)->get();

            return response()->json([
                'status' => 200,
                'message' => "Vías relacionadas con la materia $idCatMateria",
                'data' => $catVias
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
