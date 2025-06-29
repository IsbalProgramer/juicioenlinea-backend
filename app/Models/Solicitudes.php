<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitudes extends Model
{
    //
    protected $table = 'solicitudes';
    protected $primaryKey = 'idSolicitud';
    protected $fillable = [
        'idGrabacion',
        'idGeneral',
        'observaciones',
    ];

    public function grabacion()
    {
        return $this->belongsTo(Grabaciones::class, 'idGrabacion', 'idGrabacion');
    }

    public function historialEstado()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'idSolicitud', 'idSolicitud');
    }
    //ulitmo estado de la solicitud
    public function ultimoEstado()
    {
        return $this->hasOne(HistorialEstadoSolicitud::class, 'idSolicitud', 'idSolicitud')
            ->latestOfMany('fechaHora');
    }
}
