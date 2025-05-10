<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatMaterias extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_materias';
    protected $primaryKey = 'idCatMateria';
    protected $fillable = [
        'descripcion',
        'claveMateria',
        'activo',
    ];

    public function catMateriaVias()
    {
        return $this->hasMany(\App\Models\CatMateriaVia::class, 'idCatMateria');
    }


}
