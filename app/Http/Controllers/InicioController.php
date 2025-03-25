<?php

namespace App\Http\Controllers;

use App\Models\Inicio;
use App\Models\Parte;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;

class InicioController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

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
    $validator = Validator::make($request->all(), [
        'folio_preregistro' => 'required|string|max:191',
        'idCatMateria' => 'required|integer',
        'idCatVia' => 'required|integer',
        'idAbogado' => 'required|integer',
        // Validación del array de partes 
        'partes' => 'required|array|min:1',
        'partes.*.nombre' => 'required|string|max:191',
        'partes.*.apellidoPaterno' => 'required|string|max:50',
        'partes.*.apellidoMaterno' => 'required|string|max:50',
        'partes.*.idCatGenero' => 'required|integer',
        'partes.*.idCatParte' => 'required|integer',
        'partes.*.direccion' => 'required|string|max:255',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => 422,
            'errors' => $validator->messages(),
        ], 422);
    }

    try {
        DB::beginTransaction(); // Iniciar transacción

        // Crear el registro en la tabla "inicios"
        $inicio = Inicio::create([
            'folio_preregistro' => $request->folio_preregistro,
            'idCatMateria' => $request->idCatMateria,
            'idCatVia' => $request->idCatVia,
            'idAbogado' => $request->idAbogado,
        ]);

        // Insertar las partes asociadas
        foreach ($request->partes as $parte) {
            Parte::create([
                'nombre' => $parte['nombre'],
                'apellidoPaterno' => $parte['apellidoPaterno'],
                'apellidoMaterno' => $parte['apellidoMaterno'],
                'idCatGenero' => $parte['idCatGenero'],
                'idCatParte' => $parte['idCatParte'],
                'direccion' => $parte['direccion'],
                'idInicio' => $inicio->idInicio, // Relación con "inicios"
            ]);
        }

        DB::commit(); // Confirmar transacción

        return response()->json([
            'status' => 200,
            'message' => 'Inicio y partes creados exitosamente',
            'data' => $inicio
        ], 200);

    } catch (\Exception $e) {
        DB::rollBack(); // Revertir transacción en caso de error

        return response()->json([
            'status' => 500,
            'message' => 'Error al crear el inicio',
            'error' => $e->getMessage(),
        ], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(Inicio $inicio)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Inicio $inicio)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Inicio $inicio)
    {
        //
    }
}
