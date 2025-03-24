<?php

namespace App\Http\Controllers;

use App\Models\HistorialEstadoDocumento;
use Illuminate\Http\Request;
use \Illuminate\Database\QueryException;

class HistorialEstadoDocumentoController extends Controller
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
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'idDocumento' => 'required|integer|exists:documentos,id',
            'fechaEstado' => 'required|date',
            'idCatEstadoDocumento' => 'required|integer|exists:cat_estado_documentos,id',
            'idGeneral' => 'nullable|integer|exists:generales,id',
        ]);

        // Crear un nuevo historial de estado de documento
        $historialEstadoDocumento = new HistorialEstadoDocumento();
        $historialEstadoDocumento->idDocumento = $validatedData['idDocumento'];
        $historialEstadoDocumento->fechaEstado = $validatedData['fechaEstado'];
        $historialEstadoDocumento->idCatEstadoDocumento = $validatedData['idCatEstadoDocumento'];
        $historialEstadoDocumento->idGeneral = $validatedData['idGeneral'] ?? null;

        try {
            // Guardar el historial en la base de datos
            $historialEstadoDocumento->save();

            // Retornar una respuesta JSON con el historial creado
            return response()->json([
            'message' => 'Historial de estado de documento creado exitosamente.',
            'data' => $historialEstadoDocumento
            ], 201);
        } catch (QueryException $e) {
            // Manejar errores de base de datos
            return response()->json([
            'error' => 'Error al guardar el historial en la base de datos.',
            'details' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            // Manejar otros errores
            return response()->json([
            'error' => 'Ocurrió un error inesperado.',
            'details' => $e->getMessage()
            ], 500);
        }


    }

    /**
     * Display the specified resource.
     */
    public function show(HistorialEstadoDocumento $historialEstadoDocumento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HistorialEstadoDocumento $historialEstadoDocumento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HistorialEstadoDocumento $historialEstadoDocumento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistorialEstadoDocumento $historialEstadoDocumento)
    {
        //
    }
}
