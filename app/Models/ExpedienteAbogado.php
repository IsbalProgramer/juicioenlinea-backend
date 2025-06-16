<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpedienteAbogado extends Model
{
    //
    protected $table = 'expediente_abogado';
    protected $primaryKey = 'idExpedienteAbogado';
    protected $fillable = [
        'idExpediente',
        'idAbogado'
    ];
    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'idExpediente', 'idExpediente');
    }
    public function abogado()
    {
        return $this->belongsTo(Abogado::class, 'idAbogado', 'idAbogado');
    }

}

