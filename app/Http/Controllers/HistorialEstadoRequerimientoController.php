<?php

namespace App\Http\Controllers;

use App\Models\HistorialEstadoRequerimiento;
use Illuminate\Http\Request;

class HistorialEstadoRequerimientoController extends Controller
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
            'idRequerimiento' => 'required|integer|exists:requerimientos,id',
            'fechaEstado' => 'required|date',
            'idCatEstadoRequerimiento' => 'required|integer|exists:cat_estado_requerimientos,id',
            'idGeneral' => 'nullable|integer|exists:generales,id',
        ]);

        // Crear un nuevo historial de estado de requerimiento
        $historialEstadoRequerimiento = new HistorialEstadoRequerimiento();
        $historialEstadoRequerimiento->idRequerimiento = $validatedData['idRequerimiento'];
        $historialEstadoRequerimiento->fechaEstado = $validatedData['fechaEstado'];
        $historialEstadoRequerimiento->idCatEstadoRequerimiento = $validatedData['idCatEstadoRequerimiento'];
        $historialEstadoRequerimiento->idGeneral = $validatedData['idGeneral'] ?? null;
        $historialEstadoRequerimiento->save();

        // Retornar la respuesta con el recurso creado
        return response()->json([
            'message' => 'Historial de estado de requerimiento creado exitosamente.',
            'data' => $historialEstadoRequerimiento
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(HistorialEstadoRequerimiento $historialEstadoRequerimiento)
    {
        //  
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HistorialEstadoRequerimiento $historialEstadoRequerimiento)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HistorialEstadoRequerimiento $historialEstadoRequerimiento)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HistorialEstadoRequerimiento $historialEstadoRequerimiento)
    {
        //
    }
}
