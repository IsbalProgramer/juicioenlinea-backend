<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatRemitente extends Model
{
    //
    protected $table = 'cat_remitentes';
    protected $primaryKey = 'idCatRemitente';
    protected $fillable = [
        'categoria',
        'dependencia',
        'remitente',
        'cargo'

    ];

    public function juzgados()
    {
        return $this->belongsToMany(
            CatJuzgados::class,
            'cat_remitente_juzgados',
            'idCatRemitente',
            'idCatJuzgado'
        );
    }
}
