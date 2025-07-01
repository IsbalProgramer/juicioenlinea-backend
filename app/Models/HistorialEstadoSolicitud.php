<?php

namespace App\Models;

use App\Models\Catalogos\CatEstadoSolicitud;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoSolicitud extends Model
{
    //
    protected $table = 'historial_estado_solicituds';
    protected $primaryKey = 'idHistorialEstadoSolicitud';
    protected $fillable = [
        'idSolicitud',
        'idCatalogoEstadoSolicitud', //*cambiar por idCatEstadoSolicitud
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
    public function estado()
    {                                                       //*cambiar por idCatEstadoSolicitud
        return $this->belongsTo(CatEstadoSolicitud::class, 'idCatalogoEstadoSolicitud', 'idCatEstadoSolicitud');
    }

    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)
            ->setTimezone(config('app.timezone', 'America/Mexico_City'))
            ->toIso8601String();
    }
    
    public function getUpdatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)
            ->setTimezone(config('app.timezone', 'America/Mexico_City'))
            ->toIso8601String();
    }
}
