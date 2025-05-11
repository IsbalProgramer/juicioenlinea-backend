<?php

namespace App\Models;

use App\Models\Documentos;
use App\Models\User;
use App\Models\HistorialEstadoRequerimiento;
use Carbon\Carbon;
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
        'idDocumentoAcuse',
        'idDocumentoAuto',
        'idSecretario',
        'idAbogado',
        'fechaLimite',
    ];

    public function documentoAcuerdo()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAcuerdo');
    }

    public function documentoAcuse()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAcuse');
    }

    public function documentoAuto()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAuto');
    }

    public function documentoRequerimiento()
    {
        return $this->belongsToMany(Documento::class, 'documento_requerimiento', 'idRequerimiento', 'idDocumento');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoRequerimiento::class, 'idRequerimiento');
    }

    public function getFechaLimiteAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d\TH:i:s.u\Z');
    }
}
