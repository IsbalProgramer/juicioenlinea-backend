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
    public function index(Request $request) {}

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }


    /**
     * Display the specified resource.
     */
    public function show($idDocumento, Request $request)
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

            // return $response;  

            $data = $response->json();  // Este $data ya es un array asociativo

            $data['nombre'] = $documento->nombre ?? 'Sin nombre';
            $tipoDocumento = DB::table('cat_tipo_documentos')
                ->where('idCatTipoDocumento', $documento->idCatTipoDocumento)
                ->first();
            $data['descripcion'] = $tipoDocumento->descripcion ?? 'Sin descripción';

            return response()->json($data);


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

}
