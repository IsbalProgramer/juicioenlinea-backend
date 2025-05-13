<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatTipoVias extends Model
{
    // Indicar el nombre de la tabla si es necesario
    protected $table = 'cat_tipo_vias';
    protected $primaryKey = 'idCatTipoVia';
    protected $fillable = [
        'descripcion',
        'modelo',
        'activo',
    ];

    public function catMateriaVias()
    {
        return $this->hasMany(CatMateriaVia::class, 'idCatTipoVia');
    }


}
