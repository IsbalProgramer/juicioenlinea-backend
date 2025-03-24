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
        'idCatTipoRequerimiento',
        'idCatEstadoRequerimientos',
        'fechaRequerimiento',
        'fechaLimite',
        'descripcion',
        'documento',
        'idGeneral'
    ];
}
