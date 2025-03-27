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
        try {
            //$inicios = Inicio::all();
            $inicios = Inicio::with([
                'partes',
                'documentos:idExpediente,folio,nombre'
            ])->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de incios",
                'data' => $inicios
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de inicios',
                'error' => $e->getMessage(),
            ], 500);
        }
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
            'documentos' => 'required|array|min:1',
            'documentos.*.nombre' => 'required|string',
            'documentos.*.documento' => 'required|file|max:2048',

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
                'fechaCreada' => $request->fechaCreada,
            ]);

            // Insertar las partes asociadas
            $inicio->partes()->createMany($request->partes);

            $documentos = [];
            foreach ($request->documentos as $documentoData) {
                $documento = $documentoData['documento'];
                $base64Content = base64_encode($documento->get());

                $documentos[] = [
                    'nombre' => $documentoData['nombre'],
                    'documento' => DB::raw("CONVERT(VARBINARY(MAX), '$base64Content')"), // Conversión explícita
                    'folio' => $request->folio_preregistro, // Asegúrate de incluir este campo si es necesario
                ];
            }


            // Insertar los documentos asociados a este inicio
            $inicio->documentos()->createMany($documentos);


            DB::commit(); // Confirmar transacción

            return response()->json([
                'status' => 200,
                'message' => 'Inicio y partes creados exitosamente',
                'data' => $inicio->makeHidden(['documentos.documento']) // Oculta el binario
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
        return response()->json([
            'status' => 200,
            'message' => "Detalle del incio",
            'data' => $inicio
        ], 200);
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
