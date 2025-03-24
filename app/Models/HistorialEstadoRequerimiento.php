<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialEstadoRequerimiento extends Model
{
    //Clase Historial Estado Requerimiento
    protected $table = 'historial_estado_requerimientos';
    protected $primaryKey = 'idHistorialEstadoRequerimiento';
    protected $fillable = [
        'idRequerimiento',
        'fechaEstado',
        'idCatEstadoRequerimientos',
        'idGeneral'
    ];
}
