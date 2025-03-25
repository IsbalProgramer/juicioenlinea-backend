<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatEstadoRequerimiento extends Model
{
    //Clase catalogo Estado Requerimiento
    protected $table = 'cat_estado_requerimientos';
    protected $primaryKey = 'idCatEstadoRequerimientos';
    protected $fillable = [
        'nombre',
        'activo'
    ];
}
