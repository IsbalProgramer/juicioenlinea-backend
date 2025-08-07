<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatEstadoInicios extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_estado_inicios';
    protected $primaryKey = 'idCatEstadoInicio';
    protected $fillable = [
        'descripcion',
        'activo'

    ];
}
