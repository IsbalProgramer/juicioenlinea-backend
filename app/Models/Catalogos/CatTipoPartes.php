<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatTipoPartes extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_tipo_partes';
    protected $primaryKey = 'idCatTipoParte';
    protected $fillable = [
        'descripcion',
        'plural',
        'tipo',
        'activo',

    ];
}
