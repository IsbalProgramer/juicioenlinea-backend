<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\CatMaterias;
use Illuminate\Http\Request;

class CatMateriasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $catMaterias = CatMaterias::select('idCatMateria', 'descripcion', 'claveMateria')->where('activo', 1)->get();
            return response()->json([
                'status' => 200,
                'message' => "Catálogos de materias",
                'data' => $catMaterias
            ],200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener el catálogo de materias',
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
    public function show(string $id)
    {
        //
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
