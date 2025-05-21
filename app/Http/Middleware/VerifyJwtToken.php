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

        return $next($request);
    }
}
