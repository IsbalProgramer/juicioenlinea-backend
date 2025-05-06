<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatTramites extends Model
{
    protected $table = 'cat_tramites';
    protected $primaryKey = 'idCatTramite';
    protected $fillable = [
        'nombre',
        'activo'

    ];
}
