<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoSolicitud extends Model
{
    //
    protected $table = 'historial_estado_solicitudes';
    protected $primaryKey = 'idHistorialEstadoSolicitud';
    protected $fillable = [
        'idSolicitud',
        'idCatalogoEstadoSolicitud',
        'fechaEstado',
        'observaciones',
        'idDocumento',
    ];

    public function solicitud()
    {
        return $this->belongsTo(Solicitudes::class, 'idSolicitud', 'idSolicitud');
    }
    public function documento()
    {
        return $this->belongsTo(Documento::class, 'idDocumento', 'idDocumento');
    }
}
