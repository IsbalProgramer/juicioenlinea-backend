<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audiencia extends Model
{
    //
    protected $table = 'audiencias';
    protected $primaryKey = 'idAudiencia';
    protected $fillable = [
        'idExpediente',
        'title',
        'agenda',
        'start',
        'end',
        'hostEmail',
        'webLink',
        'id',
        'meetingNumber',
        'password',
    ];
    public $timestamps = true;

    public function invitados()
    {
        return $this->hasMany(Invitado::class, 'idAudiencia', 'idAudiencia');
    }
    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'idExpediente', 'idExpediente');
    }
    public function historialEstados()
    {
        return $this->hasMany(HistorialEstadoAudiencia::class, 'idAudiencia', 'idAudiencia');
    }
    public function ultimoEstado()
    {
        return $this->hasOne(HistorialEstadoAudiencia::class, 'idAudiencia', 'idAudiencia')
            ->latestOfMany('fechaHora');
    }
    public function grabaciones()
    {
        return $this->hasMany(Grabaciones::class, 'idAudiencia', 'idAudiencia');
    }
}
