<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class catVias extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_vias';
    protected $primaryKey = 'idCatVia';
    protected $fillable = [
        'nombre',
        'activo',
    ];

    public function catMateriaVias()
    {
        return $this->hasMany(\App\Models\CatMateriaVia::class, 'idCatVia');
    }
}
