<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AuthHelper
{
    public static function obtenerUserData(Request $request): ?array
    {
        $jwtPayload = $request->attributes->get('jwt_payload');

        if (!isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])) {
            return null;
        }

        return json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true);
    }

    public static function obtenerIdGeneral(Request $request): ?int
    {
        $data = self::obtenerUserData($request);
        return $data['idGeneral'] ?? null;
    }

    public static function obtenerUsuario(Request $request): ?string
    {
        $data = self::obtenerUserData($request);
        return $data['Usr'] ?? null;
    }

    public static function tienePerfil(Request $request, string $perfilBuscado): bool
    {
        $perfiles = $request->attributes->get('perfilesUsuario') ?? [];

        return collect($perfiles)->contains(function ($perfil) use ($perfilBuscado) {
            return isset($perfil['descripcion']) && strtolower(trim($perfil['descripcion'])) === strtolower(trim($perfilBuscado));
        });
    }

    public static function obtenerNombreUsuarioDesdeApi(string $usuario, string $token): string
    {
        $apiDatos = 'https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/DatosUsuario';

        try {
            $response = Http::withToken($token)
                ->timeout(60)
                ->post("$apiDatos?Usuario=" . $usuario);

            if ($response->failed()) {
                Log::error("Error al obtener datos del usuario {$usuario}: " . $response->body());
                return 'Nombre no disponible';
            }

            $data = $response->json();
            return isset($data['data']['pD_Abogados'][0]['nombre'])
                ? ucwords(strtolower($data['data']['pD_Abogados'][0]['nombre']))
                : 'Nombre no disponible';
        } catch (\Exception $e) {
            Log::error("Excepción al consultar datos del usuario {$usuario}: " . $e->getMessage());
            return 'Nombre no disponible';
        }
    }
}
