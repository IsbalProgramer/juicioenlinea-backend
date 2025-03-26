<?php

namespace App\Models;
use App\Models\Parte;
use App\Models\Documento;

use Illuminate\Database\Eloquent\Model;

class Inicio extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'inicios';
    protected $primaryKey = 'idInicio';
    protected $fillable = [
        'folio_preregistro',
        'idCatMateria',
        'idCatVia',
        'idAbogado',
        'fechaCreada',
        'archivo'
    ];

    public function partes()
    {
        return $this->hasMany(Parte::class, 'idInicio');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'folio');
    }
}
