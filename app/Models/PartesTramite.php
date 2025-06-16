<?php

namespace App\Models;

use App\Models\Catalogos\CatSexos;
use App\Models\Catalogos\CatTipoPartes;
use Illuminate\Database\Eloquent\Model;

class PartesTramite extends Model
{
    //

    protected $table = 'partes_tramites';

    protected $fillable = [
        'idUsr',
        'nombre',
        'idCatSexo',
        'idCatTipoParte',
        'direccion',
        'correo',
        'idTramite' // Llave foránea 
    ];

    public function tramite()
    {
        return $this->belongsTo(tramite::class, 'idTramite');
    }
    public function catTipoParte()
    {
        return $this->belongsTo(CatTipoPartes::class, 'idCatTipoParte', 'idCatTipoParte');
    }

    public function catSexo()
    {
        return $this->belongsTo(CatSexos::class, 'idCatSexo');
    }
}
