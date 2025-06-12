<?php

namespace App\Models;

use App\Models\Catalogos\CatEstadoTramite;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoTramite extends Model
{
    //
    protected $table = 'historial_estado_tramite';
    protected $primaryKey = 'idCatJuzgado';
    protected $fillable = [
        'idTramite',
        'idCatEstadoTramite',
        'idUsuario',
        'created_at',
        'updated_at',
    ];
public function catEstadoTramite()
    {
        return $this->belongsTo(CatEstadoTramite::class, 'idCatEstadoTramite');
    }

}
