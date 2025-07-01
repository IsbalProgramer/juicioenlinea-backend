<?php

namespace App\Models\Catalogos;

use App\Models\Audiencia;
use Illuminate\Database\Eloquent\Model;

class CatEstadoSolicitud extends Model
{
    // Clase catalogo Estado Audiencia
    protected $table = 'cat_estado_solicitudes';
    protected $primaryKey = 'idCatEstadoSolicitud';
    protected $fillable = [
        'descripcion',
        'activo'
    ];
    
}