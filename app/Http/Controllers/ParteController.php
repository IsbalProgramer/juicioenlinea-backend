<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Expediente;

class ParteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show($idExpediente)
    {
        try {
            // Cargar preregistro, partes y catálogos relacionados
            $expediente = Expediente::with([
                'preRegistro.partes.catTipoParte',
                'preRegistro.partes.catSexo',
                'abogados'
            ])->findOrFail($idExpediente);

            // Obtener todas las partes desde el preregistro
            $partes = $expediente->preRegistro ? $expediente->preRegistro->partes : collect();

            // Transformar las partes para agregar descripciones de catálogos
            $partesTransformadas = $partes->map(function ($parte) {
                return [
                    'idParte' => $parte->idParte,
                    'idPreregistro' => $parte->idPreregistro,
                    'idUsr' => $parte->idUsr,
                    'nombre' => $parte->nombre,
                    'correo' => $parte->correo,
                    'correoAlterno' => $parte->correoAlterno ?? null,
                    'idCatSexo' => $parte->idCatSexo,
                    'sexoDescripcion' => $parte->catSexo->descripcion ?? null,
                    'idCatTipoParte' => $parte->idCatTipoParte,
                    'tipoParteDescripcion' => $parte->catTipoParte->descripcion ?? null,
                    'direccion' => $parte->direccion,
                    'esAbogado' => $parte->esAbogado ? true : false,
                ];
            })->values();

            // Transformar abogados
            $abogadosTransformados = $expediente->abogados->map(function ($abogado) {
                return [
                    'idParte' => null,
                    'idPreregistro' => null,
                    'idUsr' => $abogado->idUsr,
                    'nombre' => $abogado->nombre,
                    'correo' => $abogado->correo,
                    'correoAlterno' => $abogado->correoAlterno,
                    'idCatSexo' => null,
                    'sexoDescripcion' => null,
                    'idCatTipoParte' => null,
                    'tipoParteDescripcion' => null,
                    'direccion' => null,
                    'esAbogado' => true ,
                ];
            })->values();

            // Fusionar ambos arreglos
            $partesYAbogados = $partesTransformadas->merge($abogadosTransformados)->values();

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'Partes del expediente cargadas correctamente',
                'data' => $partesYAbogados
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener las partes del expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
