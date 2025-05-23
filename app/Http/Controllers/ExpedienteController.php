<?php

namespace App\Http\Controllers;

use App\Helpers\AuthHelper;
use App\Models\Expediente;
use App\Models\PreRegistro;
use App\Services\PermisosApiService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ExpedienteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        // Obtener el payload del token desde los atributos de la solicitud
        $jwtPayload = $request->attributes->get('jwt_payload');
        $datosUsuario = $permisosApiService->obtenerDatosUsuario($jwtPayload);

        if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
            return response()->json([
                'success' => false,
                'status' => 400,
                'message' => 'No se pudo obtener el idGeneral del token',
            ], 400);
        }

        $idGeneral = $datosUsuario['idGeneral'];

        // Buscar expedientes cuyo preregistro pertenezca al usuario logueado
        $expedientes = Expediente::whereHas('preRegistro', function ($query) use ($idGeneral) {
            $query->where('idGeneral', $idGeneral);
        })->with(['preRegistro.catMateriaVia.catMateria', 'preRegistro.catMateriaVia.catVia'])->get();

        // Transformar la colección usando transform (modifica la colección original)
        $expedientes->transform(function ($expediente) {
            $preRegistro = $expediente->preRegistro;
            return [
                'idExpediente'    => $expediente->idExpediente,
                'NumExpediente'   => $expediente->NumExpediente,
                'idCatJuzgado'    => $expediente->idCatJuzgado,
                'fechaResponse'   => $expediente->fechaResponse,
                'idPreregistro'   => $expediente->idPreregistro,
                // Campos de preregistro al mismo nivel
                'folioPreregistro'   => $preRegistro?->folioPreregistro,
                'idCatMateriaVia'    => $preRegistro?->idCatMateriaVia,
                'fechaCreada'        => $preRegistro?->fechaCreada,
                'created_at_pre'     => $preRegistro?->created_at,
                // Descripciones agregadas
                'materiaDescripcion' => optional($preRegistro?->catMateriaVia?->catMateria)->descripcion,
                'viaDescripcion'     => optional($preRegistro?->catMateriaVia?->catVia)->descripcion,
            ];
        });

        return response()->json([
            'success' => true,
            'status' => 200,
            'data' => $expedientes
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'idPreregistro' => 'required|integer|exists:pre_registros,idPreregistro',
            'NumExpediente' => 'required|string|max:255',
            'idCatJuzgado' => 'required|integer',
            'fechaResponse' => 'required|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'errors' => $validator->messages(),
            ], 422);
        }

        // Verifica si el idPreregistro ya fue asignado a un expediente
        if (Expediente::where('idPreregistro', $request->idPreregistro)->exists()) {
            return response()->json([
                'success' => false,
                'status' => 409,
                'message' => 'El idPreregistro ya ha sido asignado a un expediente.',
            ], 409);
        }

        try {
            $expediente = Expediente::create([
                'idPreregistro' => $request->idPreregistro,
                'NumExpediente' => $request->NumExpediente,
                'idCatJuzgado' => $request->idCatJuzgado,
                'fechaResponse' => $request->fechaResponse,
            ]);

            return response()->json([
                'success' => true,
                'status' => 201,
                'message' => 'Expediente creado correctamente',
                'data' => $expediente
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al crear el expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * Display the specified resource.
     */
    public function show($idExpediente)
    {
        // Buscar el expediente con sus relaciones
        $expediente = Expediente::with([
            'preRegistro',
            'requerimientos',
            'tramites',
        ])->findOrFail($idExpediente);

        // Buscar todos los preregistros que tengan el mismo idPreregistro que el expediente encontrado
        $preregistros = PreRegistro::where('idPreregistro', $expediente->idPreregistro)->get();

        return response()->json([
            'success' => true,
            'status' => 200,
            'datos' => $expediente,
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Expediente $expediente)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expediente $expediente)
    {
        //
    }

    public function listarExpedientesDistintos(Request $request)
    {
        try {
            $expedientes = Expediente::select('idExpediente', 'NumExpediente')
                ->distinct()
                ->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de expedientes",
                'data' => $expedientes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function listarAbogadosPorExpediente($idExpediente, Request $request)
    {
        try {
            // Buscar el expediente y su preregistro
            $expediente = Expediente::findOrFail($idExpediente);
            $idPreregistro = $expediente->idPreregistro;

            // Obtener el preregistro relacionado
            $preregistro = PreRegistro::where('idPreregistro', $idPreregistro)->first();

            if (!$preregistro) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el preregistro para el expediente $idExpediente",
                ], 404);
            }

            // Obtener el idGeneral del preregistro (asumiendo que representa al abogado)
            $idGeneral = $preregistro->idGeneral ?? null;
            $usr = $preregistro->usr ?? null;

            if (!$idGeneral) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el idGeneral del abogado para el expediente $idExpediente",
                ], 404);
            }
            if (!$usr) {
                return response()->json([
                    'status' => 404,
                    'message' => "No se encontró el usuario del abogado para el expediente $idExpediente",
                ], 404);
            }

            // Consultar datos del usuario abogado en la API externa
            $apiDatos = 'https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/DatosUsuario';
            $response = Http::withToken($request->bearerToken())
                ->timeout(60)
                ->post("$apiDatos?usuario=" . $usr); // Cambia 'Usuario' por 'usuario'

            if ($response->failed()) {
                return response()->json([
                    'status' => 500,
                    'message' => 'Error al consultar los datos del usuario abogado.',
                    'error' => $response->body(),
                ], 500);
            }

            // Ajusta el mapeo según la estructura real de la respuesta de la API

            $token = $request->bearerToken();
            $nombre = AuthHelper::obtenerNombreUsuarioDesdeApi($usr, $token);

            $abogado = [
                'idAbogado' => $idGeneral,
                'nombre' => $nombre,
            ];

            return response()->json([
                'status' => 200,
                'message' => "Listado de abogados para el expediente $idExpediente",
                'data' => [$abogado]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de abogados por expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // public function listarExpedientesGeneralesAbogados(Request $request){
    //     try{


    //         // $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

    //         // $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
    //         //     return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'abogado';
    //         // });

    //         // if (!$tienePerfilSecretario) {
    //         //     return response()->json([
    //         //         'status' => 403,
    //         //         'message' => 'No tiene permisos.',
    //         //     ], 403);
    //         // }
    //    $expedientes = AbogadoExpediente::with([

    //         ])->distinct()
    //         ->where('idAbogado', $idGeneral)->get();
    //         return response()->json([
    //             'status' => 200,
    //             'message' => "Listado de expedientes",
    //             'data' => $expedientes
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'status' => 500,
    //             'message' => 'Error al obtener la lista de expediente',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     } 
    // }
}
