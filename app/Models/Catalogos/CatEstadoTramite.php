<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatEstadoTramite extends Model
{
       //Clase catalogo Estado tramite
    protected $table = 'cat_estado_tramite';
    protected $primaryKey = 'idCatEstadoTramite';
    protected $fillable = [
        'nombre',
        'activo',  
    ];
    
}
