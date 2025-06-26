<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PermisosApiService;

class PermisosApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, PermisosApiService $permisosApiService)
    {
        try {
            $jwtPayload = $request->attributes->get('jwt_payload');
            $datosUsuario = $permisosApiService->obtenerDatosUsuarioByToken($jwtPayload);

            if (!$datosUsuario || !isset($datosUsuario['idGeneral'])) {
                return response()->json([
                    'status' => 400,
                    'message' => 'No se pudo obtener el idGeneral del token',
                ], 400);
            }

            $idGeneral = $datosUsuario['idGeneral'];
            $token = $request->bearerToken();

            // 1. Obtener idAreaSistemaUsuario
            $idAreaSistemaUsuario = $permisosApiService->obtenerIdAreaSistemaUsuario($token, $idGeneral);

            if (!$idAreaSistemaUsuario) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No se pudo obtener el área del usuario.',
                ], 403);
            }

            // 2. Obtener perfiles del usuario
            $perfiles = $permisosApiService->obtenerPerfilesUsuario($token, $idAreaSistemaUsuario);

            if (empty($perfiles) || !isset($perfiles[0]['idSistemaPerfil'])) {
                return response()->json([
                    'success' => false,
                    'status' => 403,
                    'message' => 'No se pudo obtener el perfil del usuario.',
                ], 403);
            }

            // 3. Obtener módulos y pantallas de todos los perfiles
            $modulosPantallas = [];
            foreach ($perfiles as $perfil) {
                if (isset($perfil['idSistemaPerfil'])) {
                    $modulos = $permisosApiService->obtenerModulosYPantallasUsuario($token, $idAreaSistemaUsuario, $perfil['idSistemaPerfil']);
                    if (is_array($modulos)) {
                        foreach ($modulos as $modulo) {
                            $idModulo = $modulo['idSistemaModulo'];
                            if (!isset($modulosPantallas[$idModulo])) {
                                $modulosPantallas[$idModulo] = $modulo;
                            } else {
                                // Unir pantallas sin duplicados
                                $pantallasExistentes = $modulosPantallas[$idModulo]['pantallas'] ?? [];
                                $pantallasNuevas = $modulo['pantallas'] ?? [];
                                $todasPantallas = collect(array_merge($pantallasExistentes, $pantallasNuevas))
                                    ->unique('idPantalla')
                                    ->values()
                                    ->all();
                                $modulosPantallas[$idModulo]['pantallas'] = $todasPantallas;
                            }
                        }
                    }
                }
            }

            // Reindexar para que sea un array simple
            $modulosPantallas = array_values($modulosPantallas);

            return response()->json([
                'success' => true,
                'status' => 200,
                'message' => "Módulos y pantallas del usuario",
                'data' => $modulosPantallas
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Error al obtener los módulos y pantallas',
                'error' => $e->getMessage(),
            ], 500);
        }
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
    public function show(string $idUsr, string $idGeneral)
    {
        try {
            $token = request()->bearerToken();
            $servicio = new PermisosApiService();
            $respuesta = $servicio->obtenerDatosUsuarioByApi($token, $idUsr);

            // Buscar en pD_Abogados
            if (!empty($respuesta['data']['pD_Abogados'])) {
                foreach ($respuesta['data']['pD_Abogados'] as $abogado) {
                    if (isset($abogado['curp']) && $abogado['curp'] == $idGeneral) {
                        $partes = [];

                        if (!empty($abogado['direccionPart'])) {
                            $partes[] = trim($abogado['direccionPart']);
                        }
                        if (!empty($abogado['direccionPartNoExt'])) {
                            $partes[] = 'Ext: ' . trim($abogado['direccionPartNoExt']);
                        }
                        if (!empty($abogado['direccionPartNoInt'])) {
                            $partes[] = 'Int: ' . trim($abogado['direccionPartNoInt']);
                        }
                        if (!empty($abogado['direccionPartColonia'])) {
                            $partes[] = trim($abogado['direccionPartColonia']);
                        }
                        if (!empty($abogado['direccionPartMunicipio'])) {
                            $partes[] = trim($abogado['direccionPartMunicipio']);
                        }
                        if (!empty($abogado['direccionPartEstado'])) {
                            $partes[] = trim($abogado['direccionPartEstado']);
                        }
                        if (!empty($abogado['direccionPartCP'])) {
                            $partes[] = trim($abogado['direccionPartCP']);
                        }

                        $ext = implode(', ', $partes);

                        return response()->json([
                            'success' => true,
                            'status' => 200,
                            'message' => 'Consulta exitosa',
                            'data' => [
                                'idUsr' => $idUsr,
                                'nombre' => $abogado['nombre'] ?? '',
                                'correo' => $abogado['correo'] ?? '',
                                'correoAlterno' => $abogado['correoAlterno'] ?? '',
                                'direccion' => $ext,
                            ],
                        ], 200);
                    }
                }
            }

            // No hay coincidencias
            return response()->json([
                'success' => false,
                'status' => 404,
                'message' => 'No hay coincidencias para los datos proporcionados.',
                'data' => null
            ], 404);

        } catch (\Throwable $e) {
            // Error fatal, timeout o conexión
            return response()->json([
                'success' => false,
                'status' => 500,
                'message' => 'Ocurrió un error al consultar el servicio. Por favor revise su conexión o intente más tarde.',
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
