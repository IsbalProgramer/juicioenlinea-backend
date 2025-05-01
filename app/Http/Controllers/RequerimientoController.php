<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\HistorialEstadoRequerimiento;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use \Illuminate\Database\QueryException;
use Exception;

class RequerimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index()
    {
        try {

            $requerimiento = Requerimiento::with([
                'documentoAcuerdo',
                'documentoNuevo',
                'historial'
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
            'idSecretario' => 'required|integer',
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
            'documentoAcuerdo' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ]);


        // Verificar si el folioDocumento ya existe en la tabla Documento
        $existingFolioDocumento = Documento::where('folio', $request->folioDocumento)->exists();

        if ($existingFolioDocumento) {
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
            $documento->nombre = $request->file('documentoAcuerdo')->getClientOriginalName();
            $documento->folio = $request->folioDocumento;
            $documento->idExpediente = $request->idExpediente;
            $documento->documento = base64_encode(file_get_contents($request->file('documentoAcuerdo'))); // Convertir el archivo a base64
            $documento->save();

            // Obtener el ID del documento recién creado
            $documentoID = $documento->idDocumento ?? Documento::latest('idDocumentoAcuerdo')->first()->idDocumento;

            // Si el ID del documento no se generó, lanzar una excepción
            if (!$documentoID) {
                throw new \Exception("Error: No se generó un ID para el documento.");
            }

            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'idSecretario' => $request->idSecretario,
                'idDocumentoAcuerdo' => $documentoID,
                'fechaLimite' => $request->fechaLimite
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
                    'historial' => $historial,
                    'documento' => $documento
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
    public function show($idRequerimiento)
    {
        try {

            $requerimiento = Requerimiento::with(
                'documentoAcuerdo',
                'documentoNuevo',
                'historial',
                'secretario',

            )->findOrFail($idRequerimiento);
            return response()->json([
                'status' => 200,
                'message' => "Detalle del requerimiento",
                'data' => $requerimiento
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontro el registro",
                'data' => $e
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
            'idAbogado' => 'required|integer',
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
            // if (!is_null($requerimiento->idDocumentoNuevo)) {
            //     return response()->json([
            //         'status' => 400,
            //         'message' => 'El requerimiento se encuentra completado.',
            //     ], 400);
            // }

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
                    'idUsuario' =>  $request->idAbogado,
                    'idCatEstadoRequerimientos' => 3,

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
    public function verDocumento($idDocumento)
    {
        try {
            $documento = Documento::select('nombre', 'documento')->findOrFail($idDocumento);
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

    public function listarAcuerdo($idRequerimiento)
    {
        try {
            $requerimiento = Requerimiento::with(['documentoAcuerdo'])->findOrFail($idRequerimiento);

            $documentos = [];
            if ($requerimiento->documentoAcuerdo) {
                $requerimiento->documentoAcuerdo->tipo = 'Acuerdo';
                $documentos[] = $requerimiento->documentoAcuerdo;
            }

            return response()->json([
                'status' => 200,
                'message' => "Listado de documentos de acuerdo del requerimiento",
                'data' => $documentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarNuevoDocumento($idRequerimiento)
    {
        try {
            $requerimiento = Requerimiento::with(['documentoNuevo'])->findOrFail($idRequerimiento);

            $documentos = [];
            if ($requerimiento->documentoNuevo) {
                $requerimiento->documentoNuevo->tipo = 'Requerimiento';
                $documentos[] = $requerimiento->documentoNuevo;
            }

            return response()->json([
                'status' => 200,
                'message' => "Listado de nuevos documentos del requerimiento",
                'data' => $documentos
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Actualizar documento del requerimiento
    public function actualizarDocumento(Request $request, Requerimiento $requerimiento)
    {

        $historialSubida = DB::table('historial_estado_requerimientos')
            ->where('idRequerimiento', $requerimiento->idRequerimiento)
            ->where('idCatEstadoRequerimientos', 3) // Subida de documento
            ->orderByDesc('created_at')
            ->first();

        if (!$historialSubida) {
            return response()->json([
                'status' => 400,
                'message' => 'No se puede determinar quién subió el documento.',
            ], 400);
        }

        $fechaLimite = $requerimiento->fechaLimite;
        $abogado = $historialSubida->idUsuario;

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

        // Verificar que el requerimiento tenga un documento asociado
        if (!$requerimiento->idDocumentoNuevo) {
            return response()->json([
                'status' => 404,
                'message' => 'No existe un documento asociado para actualizar.',
            ], 404);
        }

        // Buscar el documento existente
        $documentoExistente = Documento::find($requerimiento->idDocumentoNuevo);

        if (!$documentoExistente) {
            return response()->json([
                'status' => 404,
                'message' => 'Documento no encontrado.',
            ], 404);
        }

        // Actualizar folio aunque no haya archivo
        $documentoExistente->folio = $request->folioDocumento;

        if ($request->hasFile('documentoNuevo')) {
            $archivo = $request->file('documentoNuevo');
            $nombreArchivo = $archivo->getClientOriginalName();
            $contenidoBase64 = base64_encode(file_get_contents($archivo));

            $documentoExistente->nombre = $nombreArchivo;
            $documentoExistente->documento = $contenidoBase64;
        }

        $documentoExistente->save();

        // Crear historial siempre
        $historial = HistorialEstadoRequerimiento::create([
            'idRequerimiento' => $requerimiento->idRequerimiento,
            'idExpediente' => $requerimiento->idExpediente,
            'idUsuario' => $abogado,
            'idCatEstadoRequerimientos' => 4, // Actualizado
        ]);

        DB::commit();

        return response()->json([
            'status' => 200,
            'message' => 'Documento actualizado correctamente',
            'data' => [
                'requerimiento' => $requerimiento,
                'documento' => $documentoExistente,
                'historial' => $historial,
            ]
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json([
            'status' => 500,
            'message' => 'Error al actualizar el documento',
            'error' => $e->getMessage(),
        ], 500);
    }
}
    //Eliminar documento del requerimiento
    public function eliminarDocumento(Request $request, Requerimiento $requerimiento)
    {

        $historialSubida = DB::table('historial_estado_requerimientos')
            ->where('idRequerimiento', $requerimiento->idRequerimiento)
            ->where('idCatEstadoRequerimientos', 3) // Subida de documento
            ->orderByDesc('created_at')
            ->first();

        if (!$historialSubida) {
            return response()->json([
                'status' => 400,
                'message' => 'No se puede determinar quién subió el documento.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Verificar que el requerimiento tenga un documento asignado
            if (is_null($requerimiento->idDocumentoNuevo)) {
                return response()->json([
                    'status' => 404,
                    'message' => 'No hay documento asignado al requerimiento para eliminar.',
                ], 404);
            }

            // Buscar el documento
            $documento = Documento::find($requerimiento->idDocumentoNuevo);

            if (!$documento) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Documento no encontrado.',
                ], 404);
            }

            // Eliminar el documento
            $documento->delete();

            // Quitar la referencia en el requerimiento
            $requerimiento->idDocumentoNuevo = null;
            $requerimiento->save();

            // Opcional: registrar en historial que se eliminó el documento
            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $historialSubida->idUsuario,
                'idCatEstadoRequerimientos' => 5, //5 ES ELIMINADO
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Documento eliminado correctamente.',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'historial' => $historial,
                ],
            ], 200);
        } catch (QueryException $e) {
            DB::rollBack();
            return response()->json([
                'status' => 400,
                'message' => 'Error en la base de datos',
                'error' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al eliminar el documento del requerimiento: " . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar el documento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    // Descargar documento por requerimiento


    // Cambiar el estado del requerimiento
    public function cambiarEstadoRequerimiento(Request $request, Requerimiento $requerimiento)
    {
        $request->validate([
            'accion' => 'required|in:aceptar,denegar',
        ]);

        $fechaLimite = Carbon::parse($requerimiento->fechaLimite);

        // Verificar que ya haya pasado la fecha límite
        if ($fechaLimite->gt(now())) {
            return response()->json([
                'status' => 400,
                'message' => 'No se puede cambiar el estado del requerimiento antes de la fecha límite.',
            ], 400);
        }

        try {
            DB::beginTransaction();

            // Definir el nuevo estado
            $nuevoEstado = $request->accion === 'aceptar' ? 6 : 7; // 6 es Admitido, 7 es Descartado

            // Actualizar el requerimiento
            $requerimiento->idCatEstadoRequerimientos = $nuevoEstado;
            $requerimiento->save();

            // Registrar en historial
            $historial = HistorialEstadoRequerimiento::create([
                'idRequerimiento' => $requerimiento->idRequerimiento,
                'idExpediente' => $requerimiento->idExpediente,
                'idUsuario' => $request->idSecretario,
                'idCatEstadoRequerimientos' => $nuevoEstado,
            ]);

            DB::commit();

            return response()->json([
                'status' => 200,
                'message' => 'Estado del requerimiento actualizado correctamente.',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'historial' => $historial,
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al cambiar el estado del requerimiento: ' . $e->getMessage());

            return response()->json([
                'status' => 500,
                'message' => 'Error al cambiar el estado del requerimiento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //Este metodo se utiliza para verificar si el requerimiento ya paso su
    // fecha limite e insertar el estado correspondiente
    //en el historial de requerimiento

    public function estadoRequerimientoExpiro(Requerimiento $requerimiento, Request $request)
    {
        if (!$requerimiento) {
            return response()->json([
                'status' => 404,
                'message' => 'Requerimiento no encontrado.',
            ], 404);
        }

        $fechaLimite = Carbon::parse($requerimiento->fechaLimite);

        if ($fechaLimite->lt(now())) {
            // Verificar si ya existe un registro en el historial con estado "expirado"
            $existeHistorial = HistorialEstadoRequerimiento::where('idRequerimiento', $requerimiento->idRequerimiento)
                ->where('idCatEstadoRequerimientos', 2) // 2 es el estado "expirado"
                ->exists();

            if ($existeHistorial) {
                return response()->json([
                    'status' => 200,
                    'message' => 'El requerimiento ya fue marcado como expirado previamente.',
                ], 200);
            }

            try {
                DB::beginTransaction();

                // Registrar el cambio en el historial
                $historial = HistorialEstadoRequerimiento::create([
                    'idRequerimiento' => $requerimiento->idRequerimiento,
                    'idExpediente' => $requerimiento->idExpediente,
                    'idUsuario' => $requerimiento->idSecretario,
                    'idCatEstadoRequerimientos' => 2, // 2 expirado
                ]);

                DB::commit();

                return response()->json([
                    'status' => 200,
                    'message' => 'El requerimiento ha expirado y se ha registrado en el historial.',
                    'data' => [
                        'requerimiento' => $requerimiento,
                        'historial' => $historial,
                    ],
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al actualizar el estado del requerimiento.',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'status' => 200,
            'message' => 'La fecha límite del requerimiento aún no ha pasado.',
        ], 200);
    }
}
