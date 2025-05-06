<?php

namespace App\Models;

use App\Models\Catalogos\catEstadoInicios;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoInicio extends Model
{
    protected $table = 'historial_estado_inicios';
    protected $primaryKey = 'idHistorialEstadoInicio';
    protected $fillable = [
        'idInicio',
        'idCatEstadoInicio',
        'fechaEstado',
    ];

    public function inicio()
    {
        return $this->belongsTo(PreRegistro::class, 'idPreregistro');
    }

    public function estado()
    {
        return $this->belongsTo(catEstadoInicios::class, 'idCatEstadoInicio');
    }


}
