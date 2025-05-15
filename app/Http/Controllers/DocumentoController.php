<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Http;

class DocumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
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

    // // public function store(Request $request)
    // // {
    // //     $validatedData = $request->validate([
    // //         'idExpediente' => 'required|integer',
    // //         'folio' => 'required|string|max:255|',
    // //         'nombre' => 'required|string|max:255',
    // //         'documento' => 'required|file|mimes:pdf,doc,docx|max:20480', // Máx. 20MB
    // //     ]);

    // //     try {
    // //         // Verificar si el archivo fue enviado correctamente
    // //         if (!$request->hasFile('documento') || !$request->file('documento')->isValid()) {
    // //             return response()->json(['error' => 'No se ha subido ningún archivo válido'], 400);
    // //         }

    // //         // Obtener el contenido binario del archivo
    // //         $file = $request->file('documento');
    // //         $fileContent = file_get_contents($file->getRealPath()); // Lectura binaria

    // //         // Guardar el documento en la base de datos
    // //         $documento = new Documento();
    // //         $documento->idExpediente = $validatedData['idExpediente'];
    // //         $documento->folio = $validatedData['folio'];
    // //         $documento->nombre = $validatedData['nombre'];
    // //         $documento->documento = $fileContent; // Guardar como BLOB
    // //         $documento->save();

    // //         return response()->json([
    // //             'message' => 'Documento guardado exitosamente',
    // //             'documento_id' => $documento->idDocumento,
    // //         ], 201);
    // //     } catch (\Exception $e) {
    // //         return response()->json(['error' => 'Error al guardar el documento: ' . $e->getMessage()], 500);
    // //     }
    // // }

    
    // public function store(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'idExpediente' => 'required|integer',
    //         'folio' => 'required|string|max:255',
    //         'nombre' => 'required|string|max:255',
    //         'documento' => 'required|file|mimes:pdf,doc,docx|max:20480',
    //     ]);
    
    //     try {
    //         // 🚨 **VERIFICACIÓN PREVIA: Evitar duplicados sin tocar la BD**
    //         if (Documento::where('folio', $validatedData['folio'])->exists()) {
    //             return response()->json([
    //                 'error' => 'El folio ya existe en la base de datos. No se permiten duplicados.',
    //             ], 400);
    //         }
    
    //         if (!$request->hasFile('documento') || !$request->file('documento')->isValid()) {
    //             return response()->json(['error' => 'No se ha subido ningún archivo válido'], 400);
    //         }
    
    //         $file = $request->file('documento');
    //         $fileContent = file_get_contents($file->getRealPath());
    
    //         DB::beginTransaction();
    
    //         $documento = new Documento();
    //         $documento->idExpediente = $validatedData['idExpediente'];
    //         $documento->folio = $validatedData['folio'];
    //         $documento->nombre = $validatedData['nombre'];
    //         $documento->documento = $fileContent;
    //         $documento->save();
    
    //         DB::commit();
    
    //         return response()->json([
    //             'message' => 'Documento guardado exitosamente',
    //             'documento_id' => $documento->idDocumento,
    //         ], 201);
    
    //     } catch (QueryException $e) {
    //         DB::rollBack();
    
    //         // **Registrar el error exacto en logs para depuración**
    //         Log::error('Error en la base de datos: ' . $e->getMessage());
    
    //         // **Detectar error de clave duplicada en SQL Server**
    //         if ($e->getCode() == "23000" || Str::contains($e->getMessage(), 'Cannot insert duplicate key row')) {
    //             return response()->json([
    //                 'error' => 'El folio ya existe en la base de datos. No se permiten duplicados.',
    //             ], 400);
    //         }
    
    //         return response()->json([
    //             'error' => 'Error en la base de datos, por favor intente nuevamente.',
    //         ], 500);
    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error inesperado: ' . $e->getMessage());
    
    //         return response()->json([
    //             'error' => 'Error inesperado, por favor contacte al administrador.',
    //         ], 500);
    //     }
    // }
    
    
    /**
     * Display the specified resource.
     */
    public function show($idDocumento)
    {
        try {
            $documento = Documento::select('nombre','documento')->findOrFail($idDocumento);
            return response()->json([
                'status' => 200,
                'message' => 'Detalle del documento',
                'data' => $documento
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'No se encontró el registro',
                'data' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Documento $documento)
    {
        //Editar 
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Documento $documento)
    {
        //Actualizar 
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Documento $documento)
    {
        //
        
    }

    //crear un metodo para eliminar un documento 
    public function eliminarDocumento($idDocumento)
    {
        try {
            $documento = Documento::findOrFail($idDocumento);
            $documento->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Documento eliminado exitosamente',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar el documento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver el documento
     */

    public function verDocumento($idDocumento, Request $request)
    {
        try {
            // Validar el ID del documento
            if (!$idDocumento) {
                return response()->json([
                    'status' => 400,
                    'message' => 'ID de documento no proporcionado',
                ], 400);
            }

            $apiDocumento = 'https://api.tribunaloaxaca.gob.mx/NasApi/api/Nas';


            $documento = Documento::find($idDocumento);
            if (!$documento) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Documento no encontrado',
                ], 404);
            }
            $documentoRuta = $documento->documento;
            $documentoNombre = explode('/', $documentoRuta);
            $documentoNombre = end($documentoNombre);
            $documentoRuta = str_replace($documentoNombre, '', $documentoRuta); // Eliminar el nombre del documento de la ruta

            $response = Http::withToken($request->bearerToken())
                ->get($apiDocumento . '?' . http_build_query([
                    'path' => $documentoRuta,
                    'fileName' => $documentoNombre
                ]));

            return response()->json([
                'status' => 200,
                'message' => 'Documento encontrado',
                'data' => $response->json(),
            ], 200);

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al descargar el documento',
                    'error' => $response->json(),
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'No se encontró el registro',
                'data' => $e->getMessage()
            ], 500);
        }
    }

}
