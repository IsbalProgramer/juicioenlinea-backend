<?php

namespace App\Models;

use App\Models\Catalogos\CatSexos;
use App\Models\Catalogos\CatTipoPartes;
use Illuminate\Database\Eloquent\Model;

class Parte extends Model
{
    protected $table = 'partes';

    protected $fillable = [
        'idUsr',
        'nombre',
        'idCatSexo',
        'idCatTipoParte',
        'direccion',
        'correo',
        'idPreregistro' // Llave foránea 
    ];

    /**
     * Relación con PreRegistro
     */
    public function preregistro()
    {
        return $this->belongsTo(PreRegistro::class, 'idPreregistro');
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
