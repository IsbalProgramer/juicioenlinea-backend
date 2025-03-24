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
        // Validar los datos de entrada
        $validatedData = $request->validate([
            'idExpediente' => 'required|integer|exists:expedientes,id',
            'folio' => 'required|string|max:255',
            'nombre' => 'required|string|max:255',
            'documento' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        // Manejar la carga del archivo
        if ($request->hasFile('documento')) {
            $file = $request->file('documento');
            $filePath = $file->store('documentos', 'public');
        } else {
            return response()->json(['error' => 'No se pudo cargar el archivo'], 400);
        }

        // Crear un nuevo documento
        $documento = new Documento();
        $documento->idExpediente = $validatedData['idExpediente'];
        $documento->folio = $validatedData['folio'];
        $documento->nombre = $validatedData['nombre'];
        $documento->documento = $filePath;
        $documento->save();

        return response()->json([
            'message' => 'Documento creado exitosamente',
            'documento' => $documento,
        ], 201);
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
}
