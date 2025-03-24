<?php

namespace App\Http\Controllers;

use App\Models\Requerimiento;
use Illuminate\Http\Request;

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
        // Validar los datos del requerimiento
        $validatedData = $request->validate([
            'idExpediente' => 'required|integer|exists:expedientes,id',
            'idCatTipoRequerimiento' => 'required|integer|exists:cat_tipo_requerimientos,id',
            'idCatEstadoRequerimiento' => 'required|integer|exists:cat_estado_requerimientos,id',
            'fechaRequerimiento' => 'required|date',
        ]);

        // Crear un nuevo requerimiento
        $requerimiento = new Requerimiento();
        $requerimiento->idExpediente = $validatedData['idExpediente'];
        $requerimiento->idCatTipoRequerimiento = $validatedData['idCatTipoRequerimiento'];
        $requerimiento->idCatEstadoRequerimiento = $validatedData['idCatEstadoRequerimiento'];
        $requerimiento->fechaRequerimiento = $validatedData['fechaRequerimiento'];

        // Guardar el requerimiento en la base de datos
        if ($requerimiento->save()) {
            return response()->json([
            'message' => 'Requerimiento creado exitosamente',
            'requerimiento' => $requerimiento
            ], 201);
        } else {
            return response()->json([
            'message' => 'Error al crear el requerimiento'
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
