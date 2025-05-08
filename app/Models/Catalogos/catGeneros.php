<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatGeneros extends Model
{
        // Indicar el nombre de la tabla si es necesario
        protected $table = 'cat_generos';
        protected $primaryKey = 'idCatGenero';
        protected $fillable = [
            'nombre',
            'activo'

        ];
}
