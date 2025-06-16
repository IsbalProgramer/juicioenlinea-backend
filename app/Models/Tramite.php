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
        'folioOficio',
        'sintesis',
        'observaciones',
        'idExpediente',
        'usr',
        'idDocumentoTramite',
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

    public function historial()
    {
        return $this->hasMany(HistorialEstadoTramite::class, 'idTramite');
    }

    public function documento()
    {
        return $this->belongsTo(Documento::class, 'idDocumentoTramite');
    }

     public function partesTramite()
    {
        return $this->hasMany(PartesTramite::class, 'idTramite');
    }

}
