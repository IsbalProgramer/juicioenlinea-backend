<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Requerimiento extends Model
{
    //Clase Requerimiento
    protected $table = 'requerimientos';
    protected $primaryKey = 'idRequerimiento';
    protected $fillable = [
        'idExpediente',
        'idCatEstadoRequerimientos',
        'descripcion',
        'idDocumento',
        'idSecretario',
        'folioTramite',
    ];

    public function Documentos()
    {
        return $this->hasMany(Documento::class, 'idDocumento');
    }
}
