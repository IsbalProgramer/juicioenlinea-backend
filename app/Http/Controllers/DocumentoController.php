<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;

class DocumentoController extends Controller
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
        $validatedData = $request->validate([
            'idExpediente' => 'required|integer|',
            // exists:expedientes,id',
            'folio' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'documento' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        try {
            // Obtener el contenido binario del archivo
            $fileContent = file_get_contents($request->file('documento')->getRealPath());

            // Crear el documento en la base de datos
            $documento = Documento::create([
                'idExpediente' => $validatedData['idExpediente'],
                'folio' => $validatedData['folio'],
                'nombre' => $validatedData['nombre'],
                'documento' => $fileContent, // Se guarda el archivo en formato binario
            ]);

            return response()->json([
                'message' => 'Documento guardado exitosamente',
                'documento_id' => $documento->idDocumento, // Enviar el ID para su recuperación
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al guardar el documento: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(Documento $documento)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Documento $documento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Documento $documento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Documento $documento)
    {
        //
    }

    /**
     * Download the specified resource.
     */
    public function download($id)
    {
        // Buscar el documento por ID
        $documento = Documento::find($id);
    
        if (!$documento) {
            return response()->json(['error' => 'Documento no encontrado'], 404);
        }
    
        // Devolver el documento como descarga
        return response($documento->documento)
            ->header('Content-Type', 'application/octet-stream')
            ->header('Content-Disposition', 'attachment; filename="' . $documento->nombre . '"');
    }
    
}
