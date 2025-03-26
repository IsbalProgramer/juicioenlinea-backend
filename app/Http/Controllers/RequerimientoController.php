<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\Documento;


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
        $validator = Validator::make($request->all(), [
            'idExpediente' => 'required|integer',
            'descripcion' => 'required|string',
            'idCatEstadoRequerimiento' => 'required|integer',
            'folioTramite'  => 'required|string',
            'documento' => 'required|file|mimes:pdf,doc,docx,jpg,png|max:2048', // Ajusta los tipos permitidos
            'documentoNuevo' => 'nullable|file|mimes:pdf,doc,docx,jpg,png|max:2048',
            'idSecretario' => 'required|integer',
        ]);
        
    
        if ($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }
    
        try {
            DB::beginTransaction();
    
            // Guardar el documento en la base de datos primero
            $documento = new Documento();
            $documento->nombre = $request->file('documento')->getClientOriginalName();
            $documento->folio = $request->folioTramite;
           $documento->idExpediente=$request->idExpediente;
           // $documento->contenido = mb_convert_encoding(file_get_contents($request->file('documento')), 'UTF-8', 'auto');
           $documento->documento = base64_encode(file_get_contents($request->file('documento')));

            $documento->save();
    
            // Crear el requerimiento con la referencia al documento
            $requerimiento = Requerimiento::create([
                'idExpediente' => $request->idExpediente,
                'descripcion' => $request->descripcion,
                'idCatEstadoRequerimiento' => $request->idCatEstadoRequerimiento,
                'folioTramite' => $request->folioTramite,
                'idSecretario' => $request->idSecretario,
                'idDocumento' => $documento->id // Aquí se usa el ID del documento
            ]);
    
            // Si hay un segundo documento, lo guardamos
            if ($request->hasFile('documentoNuevo')) {
                $documentoNuevo = new Documento();
                $documentoNuevo->nombre = $request->file('documentoNuevo')->getClientOriginalName();
                $documentoNuevo->mime = $request->file('documentoNuevo')->getClientMimeType();
                $documentoNuevo->contenido = mb_convert_encoding(file_get_contents($request->file('documentoNuevo')), 'UTF-8', 'auto');
                $documentoNuevo->save();
            }
    
            DB::commit();
    
            return response()->json([
                'status' => 200,
                'message' => 'Documento guardado y requerimiento creado con referencia al documento',
                'data' => [
                    'requerimiento' => $requerimiento,
                    'documento_id' => $documento->idDocumento // Retorna el ID del documento correctamente
                ]
            ], 200);
    
        } catch (\Exception $e) {
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
    public function show(Requerimiento $requerimiento)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Requerimiento $requerimiento)
    {
        //
    }
}
