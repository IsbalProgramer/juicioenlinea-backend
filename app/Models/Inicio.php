<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inicio extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'inicios';

    protected $fillable = [
        'folio_preregistro',
        'materia',
        'via',
        'archivo'
    ];
}
