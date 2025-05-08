<?php

namespace App\Http\Controllers\Catalogos;

use App\Http\Controllers\Controller;
use App\Models\Catalogos\CatTipoDocumento;
use Illuminate\Http\Request;

class CatTipoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Obtener todos los registros
            $catTipoDocumento = CatTipoDocumento::all();

            // Mover el primer registro al final y reindexar los datos
            $catTipoDocumento = $catTipoDocumento->skip(1)->push($catTipoDocumento->first())->values();

            return response()->json([
                'status' => 200,
                'message' => "Catálogos de documentos",
                'data' => $catTipoDocumento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener el catálogo de documentos',
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
