<?php

namespace App\Http\Controllers;

use App\Models\CatMateriaVia;
use App\Models\PreRegistro;
use App\Models\Parte;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;

class PreRegistroController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $preRegistros = PreRegistro::with([
                'partes',
                'documentos:idPreregistro,nombre',
                'catMateriaVia.catMateria',
                'catMateriaVia.catVia',
                'historialEstado' => function ($query) {
                    $query->latest('fechaEstado')
                        ->limit(1)
                        ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
                        ->with('estado:idCatEstadoInicio,nombre');
                }
            ])->get();

            return response()->json([
                'status' => 200,
                'message' => "Listado de preregistros",
                'data' => $preRegistros
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de preregistros',
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
            'folioPreregistro' => 'required|string|max:191',
            'idCatMateria' => 'required|integer',
            'idCatVia' => 'required|integer',
            'sintesis' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
            'partes' => 'required|array|min:1',
            'partes.*.nombre' => 'required|string|max:255',
            'partes.*.apellidoPaterno' => 'required|string|max:255',
            'partes.*.apellidoMaterno' => 'nullable|string|max:255',
            'partes.*.idCatGenero' => 'required|integer',
            'partes.*.idCatParte' => 'required|integer',
            'partes.*.direccion' => 'nullable|string|max:255',
            'documentos' => 'required|array|min:1',
            'documentos.*.idCatTipoDocumento' => 'nullable|integer',
            'documentos.*.nombre' => 'nullable|string',
            'documentos.*.documento' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        try {
            DB::beginTransaction(); // Iniciar transacción

            // Obtener el payload del token desde los atributos de la solicitud
            $jwtPayload = $request->attributes->get('jwt_payload');
            $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
                ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
                : null;

            if (!$idGeneral) {
                return response()->json([
                    'success' => false,
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            // Crear el registro en la tabla pivote "cat_materia_via"
            $catMateriaVia = CatMateriaVia::firstOrCreate([
                'idCatMateria' => $request->idCatMateria,
                'idCatVia' => $request->idCatVia,
            ]);

            // Crear el registro en la tabla "pre_registros"
            $preRegistro = PreRegistro::create([
                'folioPreregistro' => $request->folioPreregistro,
                'idCatMateriaVia' => $catMateriaVia->idCatMateriaVia, // Usar el ID generado en la tabla pivote
                'idGeneral' => $idGeneral, // Asignar idGeneral al campo idGeneral
                'fechaCreada' => now(),
                'sintesis' => $request->sintesis,
                'observaciones' => $request->observaciones,
            ]);

            // Crear el registro en la tabla "historial_estado_inicios"
            $preRegistro->historialEstado()->create([
                'idCatEstadoInicio' => 1, // Estado inicial
                'fechaEstado' => now(), // Fecha actual
            ]);

            // Insertar las partes asociadas
            $preRegistro->partes()->createMany($request->partes);

            // Modificar los documentos para manejar idCatTipoDocumento y nombre
            $documentos = collect($request->documentos)->map(function ($documento) {
                if (isset($documento['idCatTipoDocumento']) && $documento['idCatTipoDocumento'] == -1) {
                    // Si el idCatTipoDocumento es -1, almacena el nombre y el idCatTipoDocumento
                    $documento['idCatTipoDocumento'] = -1;
                } elseif (isset($documento['nombre']) && !isset($documento['idCatTipoDocumento'])) {
                    // Si solo llega el nombre, asigna -1 al idCatTipoDocumento
                    $documento['idCatTipoDocumento'] = -1;
                } elseif (isset($documento['idCatTipoDocumento']) && $documento['idCatTipoDocumento'] != -1) {
                    // Si llega un idCatTipoDocumento válido (distinto de -1), ignora el nombre
                    $documento['nombre'] = null;
                }
                return $documento;
            })->toArray();

            // Insertar los documentos asociados a este preregistro
            $preRegistro->documentos()->createMany($documentos);

            DB::commit(); // Confirmar transacción

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'PreRegistro, partes, documentos y estado inicial creados exitosamente',
                'data' => $preRegistro->makeHidden(['documentos.documento']) // Oculta el binario
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir transacción en caso de error

            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear el preregistro',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($idPreregistro)
    {
        try {
            $preRegistro = PreRegistro::with([
                'partes',
                'documentos',
                'catMateriaVia.catMateria',
                'catMateriaVia.catVia',
                'historialEstado' => function ($query) {
                    $query->latest('fechaEstado')
                        ->limit(1)
                        ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
                        ->with('estado:idCatEstadoInicio,nombre');
                }
            ])->findOrFail($idPreregistro);

            $preRegistro->historialEstado->each(function ($historial) {
                $historial->makeHidden('idCatEstadoInicio');
            });

            return response()->json([
                'status' => 200,
                'message' => "Detalle del preregistro",
                'data' => $preRegistro
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => "No se encontró el registro",
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, PreRegistro $preRegistro)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PreRegistro $preRegistro)
    {
        //
    }
}