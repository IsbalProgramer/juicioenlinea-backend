<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use Illuminate\Support\Facades\Log;
use Symfony\Polyfill\Intl\Idn\Info;


class RequerimientoController extends Controller
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
        // Validar los datos de la petición
        $validator = Validator::make($request->all(), [
            'idExpediente' => 'required|integer',
            'descripcion' => 'required|string',
            'idCatEstadoRequerimiento' => 'required|integer',
            'folioTramite'  => 'required|string',
            'documento' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'documentoNuevo' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'idSecretario' => 'required|integer',
        ]);

        // Si la validación falla, devolver un error 422
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Guardar el documento en la base de datos
            $documento = new Documento();
            $documento->nombre = $request->file('documento')->getClientOriginalName();
            $documento->folio = $request->folioTramite;
            $documento->idExpediente = $request->idExpediente;
            $documento->documento = base64_encode(file_get_contents($request->file('documento'))); // Convertir el archivo a base64
            $documento->save();

            // Obtener el ID del documento recién creado
            $documentoID = $documento->idDocumento ?? Documento::latest('idDocumento')->first()->idDocumento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$documentoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            Log::info("Documento guardado con ID: " . $documentoID, $documento->toArray());

            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'idCatEstadoRequerimiento' => $request->idCatEstadoRequerimiento,
                'folioTramite' => $request->folioTramite,
                'idSecretario' => $request->idSecretario,
                'idDocumento' => $documentoID
            ]);

            // // Si hay un segundo documento, guardarlo también
            // if ($request->hasFile('documentoNuevo')) {
            //     $documentoNuevo = new Documento();
            //     $documentoNuevo->nombre = $request->file('documentoNuevo')->getClientOriginalName();
            //     $documentoNuevo->mime = $request->file('documentoNuevo')->getClientMimeType();
            //     $documentoNuevo->documento = base64_encode(file_get_contents($request->file('documentoNuevo')));
            //     $documentoNuevo->save();

            //     Log::info("Segundo documento guardado con ID: " . $documentoNuevo->idDocumento, $documentoNuevo->toArray());
            // }

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Documento guardado y requerimiento creado con referencia al documento',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documento_id' => $documentoID
                ]
            ], 200);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();

            if ($e->getCode() == 23000) { // Error de clave duplicada
                return response()->json([
                    'status' => 400,
                    'message' => 'El folio ya existe en la base de datos. Intenta con otro folio.',
                    //'error' => $e->getMessage(),
                ], 400);
            }

            Log::error("Error en la base de datos al crear el requerimiento: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error en la base de datos al crear el requerimiento',
                //'error' => $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error general al crear el requerimiento: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al crear el requerimiento',
                //'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {

            $requerimiento = Requerimiento::findOrFail($id);

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento encontrado',

                'data' => $requerimiento,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Requerimiento no encontrado',
                //'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Requerimiento $requerimiento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Requerimiento $requerimiento)
    {
        //Subir el requerimiento 
        $requerimiento->update($request->all());
        return response()->json($requerimiento, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requerimiento $requerimiento)
    {
        //
    }

    /**
     * Descargar un documento almacenado en base64
     */
    public function descargarDocumentoPorRequerimiento($idRequerimiento)
    {
        try {
            Log::info("Intentando descargar documento del requerimiento con ID: " . $idRequerimiento);

            // Buscar el requerimiento en la base de datos
            $requerimiento = Requerimiento::findOrFail($idRequerimiento);

            // Obtener el documento asociado al requerimiento
            $documento = Documento::findOrFail($requerimiento->idDocumento);

            if (!$documento) {
                Log::error("Documento no encontrado para el requerimiento ID: " . $idRequerimiento);
                return response()->json(['error' => 'Documento no encontrado'], 404);
            }

            Log::info("Documento encontrado: " . json_encode($documento));

            // Decodificar el contenido almacenado en base64
            $contenido = base64_decode($documento->documento);
            if (!$contenido) {
                Log::error("Error al decodificar el documento con ID: " . $documento->idDocumento);
                return response()->json(['error' => 'Error al procesar el documento'], 500);
            }

            // Obtener la extensión del archivo desde el nombre original
            $extension = pathinfo($documento->nombre, PATHINFO_EXTENSION);

            // Configurar el tipo MIME según la extensión
            $mimeTypes = [
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';

            Log::info("Enviando archivo con MIME Type: " . $mimeType);

            // Retornar el archivo como respuesta de descarga
            return response($contenido)
                ->header('Content-Type', $mimeType)
                ->header('Content-Disposition', 'attachment; filename="' . $documento->nombre . '"');
        } catch (\Exception $e) {
            Log::error("Error al descargar el documento: " . $e->getMessage());
            return response()->json([
                'status' => 500,
                'message' => 'Error al descargar el documento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
