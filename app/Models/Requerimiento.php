<?php

namespace App\Models;

use App\Models\Documento;
use App\Models\User;
use App\Models\HistorialEstadoRequerimiento;
use Illuminate\Database\Eloquent\Model;

class Requerimiento extends Model
{
    //Clase Requerimiento
    protected $table = 'requerimientos';
    protected $primaryKey = 'idRequerimiento';
    

    protected $fillable = [
        'idExpediente',
        'descripcion',
        'idDocumento',
        'idDocumentoNuevo',
        'idSecretario',
        'idAbogado',
        'folioTramite',
        'fechaLimite'
    ];

    public function documento()
    {
        return $this->hasMany(Documento::class, 'idDocumento');
    }
    public function secretario()
    {
        return $this->hasMany(User::class, 'idSecretario');
    }
    public function historial()
    {
        return $this->hasMany(HistorialEstadoRequerimiento::class, 'idRequerimiento');
    }
}
