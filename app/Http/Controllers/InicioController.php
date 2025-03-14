<?php

namespace App\Http\Controllers;

use App\Models\Inicio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

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
        }else{

            $inicio = Inicio::create([
                'folio_preregistro' => $request->folio_preregistro,
                'materia' => $request->materia,
                'via' => $request->via,
                'archivo' => $request->archivo,

            ]);

            if($inicio){
                return response()->json([
                    'status' => 200,
                    'message' => 'Inicio creado exitosamente'
                ],200);
            }else{
                return response()->json([
                    'status' => 500,
                    'message' => 'Algo sucedió!'
                ],500);
            }
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
