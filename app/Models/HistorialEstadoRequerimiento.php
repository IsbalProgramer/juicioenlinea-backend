<?php

namespace App\Models;

use App\Models\Catalogos\CatEstadoRequerimiento;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoRequerimiento extends Model
{
    //Clase Historial Estado Requerimiento
    protected $table = 'historial_estado_requerimientos';
    protected $primaryKey = 'idHistorialEstadoRequerimientos';
    protected $fillable = [
        'idRequerimiento',
        'created_at',
        'updated_at',
        'idCatEstadoRequerimientos',
        'idUsuario'
    ];

    public function requerimiento()
    {
        return $this->belongsTo(Requerimiento::class, 'idRequerimiento')->orderBy('created_at');
    }

    public function catEstadoRequerimiento()
    {
        return $this->belongsTo(CatEstadoRequerimiento::class, 'idCatEstadoRequerimientos');
    }

    // public function general()
    // {
    //     return $this->belongsTo(User::class, 'idGeneral');
    // }

    

}
