<?php

namespace App\Models;

use App\Models\Documentos;
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
        'idDocumentoAcuerdo',
        'idDocumentoNuevo',
        'idSecretario',
        'idAbogado',
        'folioTramite',
        'fechaLimite',
        'folioDocumento'
    ];

    public function documentoAcuerdo()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAcuerdo');
    }

    public function documentoNuevo()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoNuevo');
    }

    public function secretario()
    {
        return $this->belongsTo(User::class, 'idSecretario');
    }

    public function abogado()
    {
        return $this->belongsTo(User::class, 'idAbogado');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoRequerimiento::class, 'idRequerimiento');
    }
}
