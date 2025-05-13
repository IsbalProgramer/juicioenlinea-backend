<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatSexos extends Model
{
        // Indicar el nombre de la tabla si es necesario
        protected $table = 'cat_sexos';
        protected $primaryKey = 'idCatSexo';
        protected $fillable = [
            'descripcion',
            'activo'

        ];
}
