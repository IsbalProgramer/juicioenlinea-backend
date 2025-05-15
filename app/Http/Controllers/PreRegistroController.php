<?php

namespace App\Http\Controllers;

use App\Models\Catalogos\CatMateriaVia;
use App\Models\PreRegistro;
use App\Models\Parte;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Database\QueryException;
use App\Services\NasApiService;

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
                        ->with('estado:idCatEstadoInicio,descripcion');
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
    public function store(Request $request, NasApiService $nasApiService)
    {
        $validator = Validator::make($request->all(), [
            'idCatMateria' => 'required|integer',
            'idCatTipoVia' => 'required|integer',
            'sintesis' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:255',
            'partes' => 'required|array|min:1',
            'partes.*.nombre' => 'required|string|max:255',
            'partes.*.apellidoPaterno' => 'required|string|max:255',
            'partes.*.apellidoMaterno' => 'nullable|string|max:255',
            'partes.*.idCatSexo' => 'required|integer',
            'partes.*.idCatTipoParte' => 'required|integer',
            'partes.*.direccion' => 'nullable|string|max:255',
            'documentos.*.idCatTipoDocumento' => 'required|integer',
            'documentos.*.nombre' => 'nullable|string',
            'documentos.*.documento' => 'required|file',
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

            // Validar datos antes de cualquier inserción
            if (!$request->has('partes') || count($request->partes) === 0) {
                throw new \Exception('Debe incluir al menos una parte.');
            }

            if (!$request->has('documentos') || count($request->documentos) === 0) {
                throw new \Exception('Debe incluir al menos un documento.');
            }

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

            // Crear el folio consecutivo
            $ultimoFolio = PreRegistro::latest('idPreregistro')->value('folioPreregistro');
            $numeroConsecutivo = $ultimoFolio ? intval(explode('/', $ultimoFolio)[0]) + 1 : 1;
            $anio = now()->year;
            $folioPreregistro = str_pad($numeroConsecutivo, 4, '0', STR_PAD_LEFT) . '/' . $anio;

            // Crear el registro en la tabla pivote "cat_materia_via"
            $catMateriaVia = CatMateriaVia::firstOrCreate([
                'idCatMateria' => $request->idCatMateria,
                'idCatTipoVia' => $request->idCatTipoVia,
            ]);

            // Crear el registro en la tabla "pre_registros"
            $preRegistro = PreRegistro::create([
                'folioPreregistro' => $folioPreregistro,
                'idCatMateriaVia' => $catMateriaVia->idCatMateriaVia,
                'idGeneral' => $idGeneral,
                'fechaCreada' => now(),
                'sintesis' => $request->sintesis,
                'observaciones' => $request->observaciones,
            ]);

            // Crear el registro en la tabla "historial_estado_inicios"
            $preRegistro->historialEstado()->create([
                'idCatEstadoInicio' => 1,
                'fechaEstado' => now(),
            ]);

            // Insertar las partes asociadas
            $preRegistro->partes()->createMany($request->partes);

            // Subir documentos al NAS y preparar datos para la base
            $documentos = [];
            $folioSoloNumero = explode('/', $folioPreregistro)[0];
            foreach ($request->documentos as $documento) {
                $file = $documento['documento'];
                $idCatTipoDocumento = $documento['idCatTipoDocumento'] ?? -1;
                $nombreDocumento = $documento['nombre'] ?? 'documento';
            
                $ruta = "PERICIALES/PREREGISTROS/{$anio}/{$folioSoloNumero}";
            
                // año_mes_dia_segundos_idTipoDocumento_nombreDocumento.ext
                $timestamp = now()->format('Y_m_d_His');
                $nombreArchivo = "{$timestamp}_{$idCatTipoDocumento}_{$nombreDocumento}.{$file->getClientOriginalExtension()}";
            
                // Subir archivo al NAS
                $nasApiService->subirArchivo($file, $ruta, $request->bearerToken(), $nombreArchivo);
            
                // Guardar en la base: nombre solo si idCatTipoDocumento == -1, si no, null
                $documentos[] = [
                    'idCatTipoDocumento' => $idCatTipoDocumento,
                    'nombre' => $idCatTipoDocumento == -1 ? $nombreDocumento : null,
                    'documento' => $ruta . '/' . $nombreArchivo,
                ];
            }

            // Insertar los documentos asociados a este preregistro
            $preRegistro->documentos()->createMany($documentos);

            DB::commit(); // Confirmar transacción

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => 'PreRegistro, partes, documentos y estado inicial creados exitosamente',
                'data' => $preRegistro->makeHidden(['documentos.documento']),
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
                'partes.catTipoParte',
                'partes.catSexo',
                'documentos.catTipoDocumento',
                'catMateriaVia.catMateria',
                'catMateriaVia.catVia',
                'historialEstado' => function ($query) {
                    $query->latest('fechaEstado')
                        ->limit(1)
                        ->select('idPreregistro', 'idCatEstadoInicio', 'fechaEstado')
                        ->with('estado:idCatEstadoInicio,descripcion');
                }
            ])->findOrFail($idPreregistro);

            // Transformar las partes para incluir solo los datos necesarios
            $preRegistro->partes->transform(function ($parte) {
                return [
                    'idParte' => $parte->idParte,
                    'idPreregistro' => $parte->idPreregistro,
                    'nombre' => $parte->nombre,
                    'apellidoPaterno' => $parte->apellidoPaterno,
                    'apellidoMaterno' => $parte->apellidoMaterno,
                    'direccion' => $parte->direccion,
                    'idCatSexo' => $parte->idCatSexo,
                    'sexoDescripcion' => $parte->catSexo->descripcion ?? null, // Solo la descripción del catálogo
                    'idCatTipoParte' => $parte->idCatTipoParte,
                    'tipoParteDescripcion' => $parte->catTipoParte->descripcion ?? null, // Solo la descripción del catálogo
                ];
            });

            // Modificar los documentos para asignar el nombre desde el catálogo si es null
            $preRegistro->documentos->transform(function ($documento) {
                // Si el nombre es null y hay un idCatTipoDocumento, asignar el nombre desde el catálogo
                if (is_null($documento->nombre) && $documento->catTipoDocumento) {
                    $documento->nombre = $documento->catTipoDocumento->nombre; // Asignar el nombre desde el catálogo
                }
                return $documento;
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
