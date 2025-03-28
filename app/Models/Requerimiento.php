<?php

namespace App\Models;
use App\Models\Documento;
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
        'folioTramite',
    ];

    public function documentos()
    {
        return $this->hasMany(Documento::class, 'idDocumento');
    }
}
