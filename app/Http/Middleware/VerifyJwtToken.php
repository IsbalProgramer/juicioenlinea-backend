<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class VerifyJwtToken
{

    // Método principal que intercepta la solicitud HTTP antes de que llegue al controlador
    public function handle(Request $request, Closure $next): Response
    {
        // Obtiene el encabezado 'Authorization' de la solicitud
        $authHeader = $request->header('Authorization');

        // Verifica si el encabezado no existe o no comienza con "Bearer "
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            // Retorna una respuesta JSON con un error y un código de estado 401 (no autorizado)
            return response()->json(['error' => 'Token no proporcionado o mal formado'], 401);
        }

        // Extrae el token eliminando la palabra "Bearer " del encabezado
        $tokenString = substr($authHeader, 7);

        // Divide el token en sus tres partes: encabezado, payload y firma
        $parts = explode('.', $tokenString);

        // Verifica que el token tenga exactamente tres partes
        if (count($parts) !== 3) {
            // Retorna un error si el token no tiene el formato correcto
            return response()->json(['error' => 'Token mal formado'], 401);
        }

        // Decodifica la parte del payload 
        $payload = json_decode(base64_decode($parts[1]), true);

        // Verifica si no se pudo decodificar el payload
        if (!$payload) {
            // Retorna un error si el payload no es válido
            return response()->json(['error' => 'No se pudo decodificar el token'], 401);
        }
        Log::info('Payload JWT:', $payload);

        // Agregar un registro temporal para inspeccionar el payload
        // Verifica si el token tiene un campo 'exp' (expiración) y si ya expiró
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            // Retorna un error si el token está expirado
            return response()->json(['error' => 'Token expirado'], 401);
        }

        // Si todo está bien, almacena el payload decodificado en los atributos de la solicitud
        // Esto permite que otros componentes accedan a los datos del token
        $request->attributes->set('jwt_payload', $payload);





// Verifica si el token tiene un campo 'http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'
        // Obtener el idGeneral del payload
        $idGeneral = isset($payload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'])
            ? json_decode($payload['http://schemas.microsoft.com/ws/2008/06/identity/claims/userdata'], true)['idGeneral']
            : null;

        if (!$idGeneral) {
            return response()->json(['error' => 'No se pudo obtener el idGeneral del token'], 400);
        }

        // Realizar la solicitud a la API externa
        $idSistema = 4171; // ID del sistema fijo
        $apiUrl = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/AreaSistemaUsuario";

        // Registrar los datos enviados
        Log::info('Datos enviados a la API externa:', [
            'idSistema' => $idSistema,
            'idGeneral' => $idGeneral,
            'token' => $request->bearerToken(),
        ]);

        // Cambiar a POST si es necesario
        $response = Http::withToken($request->bearerToken())
            ->post("$apiUrl?idSistema=$idSistema&idGeneral=$idGeneral");

        // Registrar la respuesta de la API
        if ($response->failed()) {
            Log::error('Error en la respuesta de la API externa:', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            return response()->json(['error' => 'Error al obtener el idAreaSistemaUsuario'], 500);
        }

        $responseData = $response->json();
        Log::info('Respuesta de la API externa:', $responseData);

        if (!$responseData['success'] || !isset($responseData['data']['idAreaSistemaUsuario'])) {
            return response()->json(['error' => 'No se pudo obtener el idAreaSistemaUsuario'], 400);
        }

        // Almacenar el idAreaSistemaUsuario en los atributos de la solicitud
        $request->attributes->set('idAreaSistemaUsuario', $responseData['data']['idAreaSistemaUsuario']);



        // NUEVA SOLICITUD: Obtener perfiles del usuario
        $apiUrl2 = "https://api.tribunaloaxaca.gob.mx/permisos/api/Permisos/PerfilesUsuario";

        // Registrar los datos enviados a la segunda API
        Log::info('Consultando perfiles del usuario con idAreaSistemaUsuario:', [
            'idAreaSistemaUsuario' => $responseData['data']['idAreaSistemaUsuario'],
        ]);

        $response2 = Http::withToken($request->bearerToken())
            ->post("$apiUrl2?idAreaSistemaUsuario=" . $responseData['data']['idAreaSistemaUsuario']);

        if ($response2->failed()) {
            Log::error('Error en la respuesta de perfiles:', [
                'status' => $response2->status(),
                'body' => $response2->body(),
            ]);
            return response()->json(['error' => 'Error al obtener los perfiles del usuario'], 500);
        }

        $perfilesData = $response2->json();
        Log::info('Perfiles del usuario:', $perfilesData);

        if (!$perfilesData['success'] || !isset($perfilesData['data'])) {
            return response()->json(['error' => 'No se pudo obtener los perfiles del usuario'], 400);
        }

        // Almacenar los perfiles en los atributos de la solicitud
        $request->attributes->set('perfilesUsuario', $perfilesData['data']);

        // Continuar con la solicitud
        return $next($request);
    }
}
