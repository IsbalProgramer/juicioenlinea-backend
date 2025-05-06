<?php

namespace App\Models;

use App\Models\Catalogos\CatTramites;
use Illuminate\Database\Eloquent\Model;

class Tramite extends Model
{
    protected $table = 'tramites'; // Nombre de la tabla
    protected $primaryKey = 'idTramite'; // Clave primaria

    protected $fillable = [
        'idCatTramite',
        'idGeneral',
        'tramiteOrigen',
        'folioOficio',
        'folioPreregistro',
        'sintesis',
        'observaciones',
        'fechaRecepcion',
        'idExpediente',
    ];

    // Relación con la tabla cat_tramites
    public function catTramite()
    {
        return $this->belongsTo(CatTramites::class, 'idCatTramite');
    }

    // Relación con la tabla expedientes
    public function expediente()
    {
        return $this->belongsTo(Expediente::class, 'idExpediente');
    }
}
