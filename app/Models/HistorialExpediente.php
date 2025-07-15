<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistorialExpediente extends Model
{
    protected $table = 'historial_expedientes';
    protected $primaryKey = 'idHistorialExpediente';
    public $timestamps = false;

    protected $fillable = [
        'idExpediente',
        'idEstadoExpediente', 
        'descripcion',
    ];

    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'idExpediente');
    }

    public function estado()
    {
        return $this->belongsTo(CatEstadoExpediente::class, 'idEstadoExpediente');
    }
}

