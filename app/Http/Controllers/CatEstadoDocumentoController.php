<?php

namespace App\Http\Controllers;

use App\Models\CatEstadoDocumento;
use Illuminate\Http\Request;
use \Illuminate\Validation\ValidationException;

class CatEstadoDocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Insertar un nuevo estado de documento con manejo de errores
      
            // Validar los datos de entrada
            $validatedData = $request->validate([
            'nombre' => 'required|string|max:255',
            ]);

            // Crear un nuevo estado de documento
            $catEstadoDocumento = new CatEstadoDocumento();
            $catEstadoDocumento->nombre = $validatedData['nombre'];
          
            
            try {
                $catEstadoDocumento->save();  

            // Retornar respuesta exitosa
            return response()->json([
            'success' => true,
            'message' => 'Estado de documento creado exitosamente.',
            'data' => $catEstadoDocumento,
            ], 201);
        } catch (ValidationException $e) {
            // Manejar errores de validación
            return response()->json([
            'success' => false,
            'message' => 'Error de validación.',
            'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Manejar otros errores
            return response()->json([
            'success' => false,
            'message' => 'Ocurrió un error al crear el estado de documento.',
            'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(CatEstadoDocumento $catEstadoDocumento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CatEstadoDocumento $catEstadoDocumento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CatEstadoDocumento $catEstadoDocumento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CatEstadoDocumento $catEstadoDocumento)
    {
        //
    }
}
