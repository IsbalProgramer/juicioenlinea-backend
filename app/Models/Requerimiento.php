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
        'idDocumentoOficioRequerimiento',
        'descripcionRechazo',
        'idSecretario',
        'usuarioSecretario',
        'idAbogado',
        'fechaLimite',
        'folioRequerimiento'
    ];

    public function documentoAcuerdo()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAcuerdo');
    }

    public function documentoAcuse()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoAcuse');
    }

    public function documentoOficioRequerimiento()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoOficioRequerimiento');
    }

    public function documentosRequerimiento()
    {
        return $this->belongsToMany(Documento::class, 'documento_requerimiento', 'idRequerimiento', 'idDocumento');
    }

    public function historial()
    {
        return $this->hasMany(HistorialEstadoRequerimiento::class, 'idRequerimiento');
    }
    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'idExpediente');
    }
    public function getFechaLimiteAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d\TH:i:s.u\Z');
    }

    public function abogado()
    {
        return $this->belongsTo(abogado::class, 'idAbogado');
    }
}
