<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parte extends Model
{
    protected $table = 'partes';

    protected $fillable = [ 
        'nombre',
        'apellidoPaterno',
        'apellidoMaterno',
        'idCatGenero',
        'idCatParte',
        'direccion',
        'idInicio' // Llave foránea 
    ];

    public function inicio(){
        return $this->belongsTo(Inicio::class, 'idInicio');
    }
}
