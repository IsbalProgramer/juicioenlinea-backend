<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatPartes extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_tipo_partes';
    protected $primaryKey = 'idCatParte';
    protected $fillable = [
        'nombre',
        'activo'

    ];
}
