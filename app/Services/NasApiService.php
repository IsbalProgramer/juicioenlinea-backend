<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NasApiService
{
    protected $apiUrl;

    public function __construct()
    {
        $this->apiUrl = config('services.nas_api.url'); // Configura la URL en config/services.php
    }

    public function subirArchivo($file, $path, $token, $nombreArchivo = null)
    {
        $nombreArchivo = $nombreArchivo ?? $file->getClientOriginalName();

        $response = Http::withToken($token)
            ->attach('file', file_get_contents($file), $nombreArchivo)
            ->post($this->apiUrl, [
                'path' => $path,
            ]);

        // Log de la respuesta de la API del NAS
        Log::info('NAS API response', [
            'path' => $path,
            'nombreArchivo' => $nombreArchivo,
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return $response->json();
    }
}