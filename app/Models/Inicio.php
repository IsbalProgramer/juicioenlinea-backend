<?php

namespace App\Models;
use App\Models\Parte;
use App\Models\Documento;

use Illuminate\Database\Eloquent\Model;

class Inicio extends Model
{
    
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

    /**
     * Get the expediente associated with the Inicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function expediente(): HasOne
    {
        return $this->hasOne(Expediente::class);
    }
}
