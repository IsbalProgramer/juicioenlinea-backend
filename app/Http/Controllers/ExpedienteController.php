<?php

namespace App\Http\Controllers;

use App\Models\AbogadoExpediente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpedienteController extends Controller
{
    public function listarExpedientesDistintos(Request $request)
    {
        try {

            // // Obtener el payload del token desde los atributos de la solicitud
            // $jwtPayload = $request->attributes->get('jwt_payload');

            // // Agregar un registro temporal para inspeccionar el payload
            // $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
            //     ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
            //     : null;

            // if (!$idGeneral) {
            //     return response()->json([
            //         'status' => 400,
            //         'message' => 'No se pudo obtener el idGeneral del token',
            //     ], 400);
            // }

            // $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            // $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
            //     return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            // });

            // if (!$tienePerfilSecretario) {
            //     return response()->json([
            //         'status' => 403,
            //         'message' => 'No tiene permisos.',
            //     ], 403);
            // }

            $expediente = AbogadoExpediente::with([
            ])->select('idExpediente')->distinct()->get();
            return response()->json([
                'status' => 200,
                'message' => "Listado de expedientes",
                'data' => $expediente
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

            // // Obtener el payload del token desde los atributos de la solicitud
            // $jwtPayload = $request->attributes->get('jwt_payload');

            // // Agregar un registro temporal para inspeccionar el payload
            // $idGeneral = isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
            //     ? json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
            //     : null;

            // if (!$idGeneral) {
            //     return response()->json([
            //         'status' => 400,
            //         'message' => 'No se pudo obtener el idGeneral del token',
            //     ], 400);
            // }

            // $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

            // $tienePerfilSecretario = collect($perfiles)->contains(function ($perfil) {
            //     return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === 'secretario';
            // });

            // if (!$tienePerfilSecretario) {
            //     return response()->json([
            //         'status' => 403,
            //         'message' => 'No tiene permisos.',
            //     ], 403);
            // }

            $abogados = AbogadoExpediente::select('idAbogado')
                ->where('abogado_expediente.idExpediente', str_replace('-', '/', $idExpediente))
                ->distinct()
                ->get();

            return response()->json([
                'status' => 200,
                'message' => "Listado de abogados para el expediente $idExpediente",
                'data' => $abogados
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener la lista de abogados por expediente',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
