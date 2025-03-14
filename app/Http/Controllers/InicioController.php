<?php

namespace App\Http\Controllers;

use App\Models\Inicio;
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
        $validator = Validator::make($request->all(),[
            'folio_preregistro' => 'required|string|max:191',
            'materia' => 'required|string|max:191',
            'via' => 'required|string|max:191',
        ]);

        if($validator->fails()) {
            return response()->json([
                'status' => 422,
                'errors' => $validator->messages()
            ],422);
        }

        try {
            // Intentamos guardar el registro en la base de datos
            $inicio = Inicio::create([
                'folio_preregistro' => $request->folio_preregistro,
                'materia' => $request->materia,
                'via' => $request->via,
                'archivo' => $request->archivo,
            ]);
    
            return response()->json([
                'status' => 200,
                'message' => 'Inicio creado exitosamente' 
            ], 200);
    
        } catch (QueryException $e) {
            // Capturamos el error de la base de datos y lo mostramos en JSON
            return response()->json([
                'status' => 500,
                'message' => 'Error al insertar en la base de datos '
            ], 500);
        }
        catch (\Exception $e) {
            // Captura cualquier otro tipo de error
            return response()->json([
                'status' => 500,
                'message' => 'Error inesperado ' 
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
