<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatVias extends Model
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

        /**
     * Relación con CatMaterias
     */
    public function catMateria()
    {
        return $this->belongsTo(CatMaterias::class, 'idCatMateria');
    }
}
