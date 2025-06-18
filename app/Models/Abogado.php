<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Abogado extends Model
{
    //
    protected $table = 'abogados';
    protected $primaryKey = 'idAbogado';
    protected $fillable = [
        'idUsr',
        'idGeneral',
        'nombre',
        'correo',
        'correoAlterno'
    ];

    
    public function expedientes()
    {
        return $this->belongsToMany(Expediente::class, 'expediente_abogado', 'idAbogado', 'idExpediente');
    }
}
