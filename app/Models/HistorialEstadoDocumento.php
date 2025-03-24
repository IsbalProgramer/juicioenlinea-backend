<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoDocumento extends Model
{
    // Clase Historial Estado Documento
    protected $table = 'historial_estado_documento';
    protected $primaryKey = 'idHistorialEstadoDocumento';
    protected $fillable = [
        'idDocumento',
        'fechaEstado',
        'idCatEstadoDocumento',
        'idGeneral'
    ];
}
