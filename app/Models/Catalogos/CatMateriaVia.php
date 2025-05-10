<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatMateriaVia extends Model
{
        // Indicar el nombre de la tabla si es necesario
        protected $table = 'cat_materia_via';
        protected $primaryKey = 'idCatMateriaVia';
        protected $fillable = [
            'idCatMateria',
            'idCatTipoVia',
            'activo'

        ];
 
}
