<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatEstadoDocumento extends Model
{
    //Clase catalogo Documentos
    protected $table = 'cat_estado_documento';
    protected $primaryKey = 'idCatalogoEstadoDocumento';
    protected $fillable = [
        'nombre'
    ];
}
