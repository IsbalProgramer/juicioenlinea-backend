<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatVias extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_tipo_vias';
    protected $primaryKey = 'idCatTipoVia';
    protected $fillable = [
        'discripcion',
        'modelo',
        'activo',
    ];

    public function catMateriaVias()
    {
        return $this->hasMany(\App\Models\CatMateriaVia::class, 'idCatTipoVia');
    }


}
