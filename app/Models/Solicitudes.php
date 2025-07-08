<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitudes extends Model
{
    //
    protected $table = 'solicitudes';
    protected $primaryKey = 'idSolicitud';
    protected $fillable = [
        'idAudiencia',
        'folio', // Asegúrate de que este campo exista en tu base de datos
        'idGeneral',
        'observaciones',
    ];

    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class, 'idAudiencia', 'idAudiencia');
    }

    // Si quieres acceder a las grabaciones desde la solicitud:
    public function grabaciones()
    {
        return $this->hasManyThrough(
            Grabaciones::class,
            Audiencia::class,
            'idAudiencia', // Foreign key on Audiencia
            'idAudiencia', // Foreign key on Grabaciones
            'idAudiencia', // Local key on Solicitudes
            'idAudiencia'  // Local key on Audiencia
        );
    }

    public function historialEstado()
    {
        return $this->hasMany(HistorialEstadoSolicitud::class, 'idSolicitud', 'idSolicitud');
    }
    public function primerEstado()
    {
        return $this->hasOne(HistorialEstadoSolicitud::class, 'idSolicitud', 'idSolicitud')
            ->oldestOfMany('fechaEstado');
    }
    public function ultimoEstado()
    {
        return $this->hasOne(HistorialEstadoSolicitud::class, 'idSolicitud', 'idSolicitud')
            ->latestOfMany('fechaEstado');
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
