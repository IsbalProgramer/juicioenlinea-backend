<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\HistorialEstadoRequerimiento;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Exception;

class RequerimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        // $query = Requerimiento::query();

        // $fechaInicio = request()->query('fechaInicio');
        // $fechaFin = request()->query('fechaFin');

        // if ($fechaInicio && $fechaFin) {
        //     $query->whereBetween('created_at', [$fechaInicio, $fechaFin]);
        // } elseif ($fechaInicio) {
        //     $query->where('created_at', '>=', $fechaInicio);
        // } elseif ($fechaFin) {
        //     $query->where('created_at', '<=', $fechaFin);
        // }

        // $requerimientos = $query->get();

        // return response()->json($requerimientos, 200);

        try {

            $requerimiento = Requerimiento::with([
               'documento',
               'historial',
            ])->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de requerimientos",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de requeirimientos',
                'error' => $e->getMessage(),
            ], 500);
        }
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

            //Validaciones del requerimiento
            'idExpediente' => 'required|integer',
            'descripcion' => 'required|string',
            'folioTramite'  => 'required|string|unique:requerimientos',
            'idSecretario' => 'required|integer',
            'idAbogado' => 'required|integer',
            'fechaLimite' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if (Carbon::parse($value)->lte(now())) {
                        $fail('La fecha límite debe ser posterior a la fecha actual.');
                    }
                }
            ],

            //validaciones del documento
            'folioDocumento' => 'required|string',
            'documento' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);


        // Validar que el folioDocumento y el folioTramite no sean iguales
        if (strcasecmp($request->folioDocumento, $request->folioTramite) === 0) {
            return response()->json([
                'status' => 400,
                'message' => 'El folio de documento y el folio de trámite no pueden ser iguales.',
            ], 400);
        }

        // Verificar si el folioTramite ya existe en la tabla Requerimiento
        $existingFolioTramite = Requerimiento::where('folioTramite', $request->folioTramite)->exists();

        // Verificar si el folioDocumento ya existe en la tabla Documento
        $existingFolioDocumento = Documento::where('folio', $request->folioDocumento)->exists();

        if ($existingFolioTramite && $existingFolioDocumento) {
            return response()->json([
                'status' => 400,
                'message' => 'El folio de trámite y el folio de documento ya existen',
            ], 400);
        } elseif ($existingFolioTramite) {
            return response()->json([
                'status' => 400,
                'message' => 'El folio de trámite ya existe',
            ], 400);
        } elseif ($existingFolioDocumento) {
            return response()->json([
                'status' => 400,
                'message' => 'El folio de documento ya existe',
            ], 400);
        }

           // Si la validación falla, devolver un error 422
           if ($validator->fails()) {
            $errors = $validator->messages()->all();
            $errorMessage = implode(', ', $errors);
            return response()->json([
                'status' => 422,
                'message' => '' . $errorMessage,
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Guardar el documento en la base de datos
            $documento = new Documento();
            $documento->nombre = $request->file('documento')->getClientOriginalName();
            $documento->folio = $request->folioDocumento;
            $documento->idExpediente = $request->idExpediente;
            $documento->documento = base64_encode(file_get_contents($request->file('documento'))); // Convertir el archivo a base64
            $documento->save();

            // Obtener el ID del documento recién creado
            $documentoID = $documento->idDocumento ?? Documento::latest('idDocumento')->first()->idDocumento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$documentoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'folioTramite' => $request->folioTramite,
                'idSecretario' => $request->idSecretario,
                'idDocumento' => $documentoID,
                'idAbogado'=>$request->idAbogado,
                'fechaLimite'=>$request->fechaLimite
            ]);

            // Obtener el ID del requerimiento recién creado
            $requerimientoID = $requerimiento->idRequerimiento ?? Requerimiento::latest('idRequerimiento')->first()->idRequerimiento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$requerimientoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimientoID,
                'idExpediente' => $request->idExpediente,
                'idUsuario' => $request->idSecretario,
                // 'idCatEstadoRequerimientos' => 1,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Documento guardado y requerimiento creado con referencia al documento',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documento_id' => $documentoID,
                    'historial' => $historial
                ]
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error en la base de datos al crear el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 500,
                'message' => 'Error al crear el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $requerimiento = Requerimiento::with('documento')->findOrFail($id);

            // Obtener el documento asociado al requerimiento
            $documento = Documento::find($requerimiento->idDocumento);

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento encontrado',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documento' => $documento,
                ],
            ], 200);
        } catch (ModelNotFoundException $e) {

            return response()->json([
                'status' => 404,
                'message' => 'Requerimiento no encontrado',
            ], 404);
        } catch (Exception $e) {

            return response()->json([
                'status' => 500,
                'message' => 'Error al cargar el requerimiento',
            ], 500);
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
    
        // Obtener la fecha limite del requerimiento
        $fechaLimite = $requerimiento->fechaLimite;
        $abogado = $requerimiento->idAbogado;
        // Validar que la fecha limite no haya pasado
        if (Carbon::parse($fechaLimite)->lt(now())) {
            return response()->json([
                'status' => 400,
                'message' => 'No se puede modificar el requerimiento porque la fecha límite ya ha pasado.',
            ], 400);
        }
      

        $validator = Validator::make($request->all(), [
            'folioDocumento' => 'required|string',
            'documentoNuevo' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            DB::beginTransaction();
            //Verificar si ya tiene un idDocumentoNuevo y evitar la modificación
            if (!is_null($requerimiento->idDocumentoNuevo)) {
                return response()->json([
                    'status' => 400,
                    'message' => 'El requerimiento se encuentra completado.',
                ], 400);
            }

            // 
            if ($request->hasFile('documentoNuevo')) {
                $archivo = $request->file('documentoNuevo');
                $nombreArchivo = $archivo->getClientOriginalName();
                $contenidoBase64 = base64_encode(file_get_contents($archivo));

                $documentoNuevo = new Documento();
                $documentoNuevo->nombre = $nombreArchivo;
                $documentoNuevo->folio = $request->folioDocumento;
                $documentoNuevo->idExpediente = $requerimiento->idExpediente;
                $documentoNuevo->documento = $contenidoBase64;
                $documentoNuevo->save();

                // Actualizar `idDocumentoNuevo` en el requerimiento
                $requerimiento->idDocumentoNuevo = $documentoNuevo->idDocumento;

                $historial = HistorialEstadoRequerimiento::create([
                    'idRequerimiento' => $requerimiento->idRequerimiento,
                    'idExpediente' => $request->idExpediente,
                    'idUsuario' =>  $abogado,
                    'idCatEstadoRequerimientos' => 2,

                ]);
            }

            // Actualizar los demás datos del requerimiento

            $requerimiento->save();

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Requerimiento subido correctamente',
                'data' => [
                    $requerimiento,
                    $historial,
                ]
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            if ($e->getCode() == 23000) { // Error de clave duplicada
                return response()->json([
                    'status' => 400,
                    'message' => 'El folio del documento ya existe.',
                    'error' => $e->getMessage(),
                ], 400);
            }

            return response()->json([
                'status' => 400,
                'message' => 'Error en la base de datos',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al subir el requerimiento: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al subir el requerimiento',
                'error' => $e->getMessage(),
            ], 500);
        }
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
