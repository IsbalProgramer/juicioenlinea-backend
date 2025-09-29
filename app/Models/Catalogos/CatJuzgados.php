<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatJuzgados extends Model
{
    //

    protected $table = 'OPV_CatJuzgados'; // Nombre de la tabla
    protected $primaryKey = 'IdCatJuzgado';
    protected $fillable = [
        'Descripcion',
        //'lugar',
        //'activo',
    ];

    public function remitentes()
    {
        return $this->belongsToMany(
            CatRemitente::class,
            'cat_remitente_juzgados',
            'idCatJuzgado',
            'idCatRemitente'
        );
    }
}
