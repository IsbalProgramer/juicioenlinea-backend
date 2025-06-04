<?php

namespace App\Models;

use App\Models\Catalogos\CatEstadoAudiencia;
use Illuminate\Database\Eloquent\Model;

class HistorialEstadoAudiencia extends Model
{
    // Clase Historial Estado Audiencia
    protected $table = 'historial_estado_audiencias';
    protected $primaryKey = 'idHistorialEstadoAudiencia';
    protected $fillable = [
        'idAudiencia',
        'idCatalogoEstadoAudiencia',
        'fechaHora',
        'observaciones',
    ];

    public $timestamps = false;

    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class, 'idAudiencia', 'idAudiencia');
    }

    public function catalogoEstadoAudiencia()
    {
        return $this->belongsTo(CatEstadoAudiencia::class, 'idCatalogoEstadoAudiencia', 'idCatalogoEstadoAudiencia');
    }

}
