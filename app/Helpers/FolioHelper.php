<?php

namespace App\Helpers;

use App\Models\Documento;
use App\Models\Tramite;

class FolioHelper
{
    public static function generarFolio($idExpediente)
    {
        $anioActual = now()->year;

        // Obtener todos los folios del expediente actual para el año actual
        $foliosExistentes = Documento::where('idExpediente', $idExpediente)
            ->whereNotNull('folio')
            ->pluck('folio')
            ->filter(function ($folio) use ($anioActual) {
                return preg_match('/^\d{4}\/' . $anioActual . '$/', $folio);
            })
            ->map(function ($folio) {
                return (int) explode('/', $folio)[0];
            })
            ->sort()
            ->values();

        // Generar el siguiente número de folio sin repetir
        $numeroFolio = 1;
        foreach ($foliosExistentes as $folio) {
            if ($folio == $numeroFolio) {
                $numeroFolio++;
            } else {
                break; // Si hay un hueco, lo usamos
            }
        }

        $folioFormateado = str_pad($numeroFolio, 4, '0', STR_PAD_LEFT);
        return "{$folioFormateado}/{$anioActual}";
    }
}
