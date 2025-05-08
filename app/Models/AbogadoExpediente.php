<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AbogadoExpediente extends Model
{
    protected $table = 'abogado_expediente';
    protected $primaryKey = 'idAbogadoExpediente';
    protected $fillable = [
        'idExpediente',
        'idAbogado',
        'activo'
    ];

}
