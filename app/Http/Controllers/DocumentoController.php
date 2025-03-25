<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;

class DocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Validar que se envíe el id del usuario
        $validatedData = $request->validate([
            'idUsuario' => 'required|integer',
        ]);

        // Obtener todos los documentos del usuario específico
        $documentos = Documento::where('idUsuario', $validatedData['idUsuario'])->get();

        return response()->json($documentos);
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
    
    //  public function store(Request $request)
    //  {
    //      $validatedData = $request->validate([
    //          'idExpediente' => 'required|integer', // Eliminamos exists:expedientes,id
    //          'folio' => 'required|string|max:255',
    //          'nombre' => 'required|string|max:255',
    //         //  'documento' => 'nullable|string',
    //         'documento' => 'required|file|mimes:pdf,doc,docx|max:2048',
    //      ]);
     
    //      try {
    //          // Verifica si el archivo fue enviado correctamente
    //          if (!$request->hasFile('documento') || !$request->file('documento')->isValid()) {
    //              return response()->json(['error' => 'No se ha subido ningún archivo válido'], 400);
    //          }
     
    //          // Obtener el contenido binario del archivo
    //          $file = $request->file('documento');
    //          $fileContent = file_get_contents($file->getRealPath());
     
    //          // Guardar el documento en la base de datos
    //          $documento = new Documento();
    //          $documento->idExpediente = $validatedData['idExpediente'];
    //          $documento->folio = $validatedData['folio'];
    //          $documento->nombre = $validatedData['nombre'];
    //         //  $documento->documento = $validatedData['documento'] ?? null; // Si no se envía, se guarda como NULL
        
    //         $documento->documento = base64_encode($fileContent); // Se guarda como Base64
    //         $documento->save();
            
    //          $documento->save();
     
    //          return response()->json([
    //              'message' => 'Documento guardado exitosamente',
    //              'documento_id' => $documento->idDocumento,
    //          ], 201);
    //      } catch (\Exception $e) {
    //          return response()->json(['error' => 'Error al guardar el documento '. $e->getMessage() ], 500);
    //      }
    //  }
     

    public function store(Request $request)
{
    $validatedData = $request->validate([
        'idExpediente' => 'required|integer',
        'folio' => 'required|string|max:255',
        'nombre' => 'required|string|max:255',
        'documento' => 'required|file|mimes:pdf,doc,docx|max:20480', // Máx. 20MB
    ]);

    try {
        // Verificar si el archivo fue enviado correctamente
        if (!$request->hasFile('documento') || !$request->file('documento')->isValid()) {
            return response()->json(['error' => 'No se ha subido ningún archivo válido'], 400);
        }

        // Obtener el contenido binario del archivo
        $file = $request->file('documento');
        $fileContent = file_get_contents($file->getRealPath()); // Lectura binaria

        // Guardar el documento en la base de datos
        $documento = new Documento();
        $documento->idExpediente = $validatedData['idExpediente'];
        $documento->folio = $validatedData['folio'];
        $documento->nombre = $validatedData['nombre'];
        $documento->documento = $fileContent; // Guardar como BLOB
        $documento->save();

        return response()->json([
            'message' => 'Documento guardado exitosamente',
            'documento_id' => $documento->idDocumento,
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
