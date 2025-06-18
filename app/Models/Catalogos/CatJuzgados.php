<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatJuzgados extends Model
{
    //

    protected $table = 'cat_juzgados';
    protected $primaryKey = 'idCatJuzgado';
    protected $fillable = [
        'nombre',
        'lugar',
        'activo',
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
