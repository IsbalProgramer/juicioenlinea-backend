<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class catMaterias extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_materias';
    protected $primaryKey = 'idCatMateria';
    protected $fillable = [
        'nombre',
        'activo'

    ];
}
