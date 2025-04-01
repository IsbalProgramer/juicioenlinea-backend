<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatEstadoRequerimiento extends Model
{
    //Clase catalogo Estado Requerimiento
    protected $table = 'cat_estado_requerimientos';
    protected $primaryKey = 'idCatEstadoRequerimientos';
    protected $fillable = [
        'nombre',
        'activo',
        
    ];
}
