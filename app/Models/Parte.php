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
        'idPreregistro' // Llave foránea 
    ];

    /**
     * Relación con PreRegistro
     */
    public function preregistro()
    {
        return $this->belongsTo(PreRegistro::class, 'idPreregistro');
    }
}
