<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\File;

class FirmaElectronica
{
    protected $baseUrl = 'https://interconexion.tribunaloaxaca.gob.mx/api/FirmaElectronica/Firmar';

    /**
     * Firma un solo documento y retorna únicamente el archivo firmado (File).
     *
     * @param \Illuminate\Http\UploadedFile $firmaPfx
     * @param string $contrasena
     * @param \Illuminate\Http\UploadedFile $documento
     * @param string $token
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    public function firmarDocumento($firmaPfx, $contrasena, $documento, $token)
    {
        $response = Http::withToken($token)
            ->attach('archivoFirmar', file_get_contents($documento), $documento->getClientOriginalName())
            ->attach('archivoPfx_Efirma', file_get_contents($firmaPfx), $firmaPfx->getClientOriginalName())
            ->asMultipart()
            ->post($this->baseUrl, [
                'password_Efirma' => $contrasena,
            ]);

        $json = $response->json();
        Log::error('Response body FirmaElectronica: ' . $response->body());
        
        // Si la API indica error explícito
        if (isset($json['success']) && $json['success'] === false) {
            // Loguea el body crudo y lanza exactamente esa respuesta sin transformar
            Log::error('FirmaElectronica API error response: ' . $response->body());
            throw new \RuntimeException($response->body());
        }

        // Toma evidenciaFileContent desde data o raíz
        $base64 = $json['data']['evidenciaFileContent'] ?? ($json['evidenciaFileContent'] ?? null);
        if (!$base64) {
            throw new \RuntimeException('No se recibió el documento firmado.');
        }

        // Guarda a archivo temporal (por defecto .pdf)
        $tempPath = storage_path('app/tmp_' . uniqid() . '.pdf');
        file_put_contents($tempPath, base64_decode($base64));

        // Retorna solo el archivo (File). El caller puede borrar el temp tras usarlo.
        return new File($tempPath);
    }
}
