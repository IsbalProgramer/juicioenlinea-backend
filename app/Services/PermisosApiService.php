<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;

class PermisosApiService
{
    public function obtenerDatosUsuarioByToken($jwtPayload)
    {
        if (!isset($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])) {
            return null;
        }
        $userData = json_decode($jwtPayload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true);
    
        return [
            'idGeneral' => $userData['idGeneral'] ?? null,
            'Usr' => $userData['Usr'] ?? null,
            'nombre' => $userData['nombre'] ?? null,
        ];
    }

    public function obtenerIdAreaSistemaUsuario($token, $idGeneral, $idSistema = 4171)
    {
        $apiUrl = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/AreaSistemaUsuario";
        $response = Http::withToken($token)
        ->timeout(60)   
        ->post("$apiUrl?idSistema=$idSistema&idGeneral=$idGeneral");

        if ($response->failed() || !($response->json()['success'] ?? false)) {
            return null;
        }
        return $response->json()['data']['idAreaSistemaUsuario'] ?? null;
    }

    public function obtenerPerfilesUsuario($token, $idAreaSistemaUsuario)
    {
        $apiUrl = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/PerfilesUsuario";
        $response = Http::withToken($token)
            ->post("$apiUrl?idAreaSistemaUsuario=$idAreaSistemaUsuario");

        if ($response->failed() || !($response->json()['success'] ?? false)) {
            return null;
        }
        return $response->json()['data'] ?? null;
    }

    public function obtenerModulosYPantallasUsuario($token, $idAreaSistemaUsuario, $idPerfilUsuario)
    {
        $apiUrl = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/ObtenerModulosPantasUsuario";
        $response = Http::withToken($token)
            ->post("$apiUrl?IdAreaSistemaUsuario=$idAreaSistemaUsuario&IdPerfilUsuario=$idPerfilUsuario");

        if ($response->failed() || !($response->json()['success'] ?? false)) {
            return null;
        }
        return $response->json()['data'] ?? null;
    }
    
    public function obtenerDatosUsuarioByApi($token, $idUsr)
    {
        $apiUrl = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/DatosUsuario";
        $response = Http::withToken($token)
            ->post("$apiUrl?Usuario=$idUsr");

        return $response->json(); // <-- regresa todo el JSON de la respuesta
    }
}