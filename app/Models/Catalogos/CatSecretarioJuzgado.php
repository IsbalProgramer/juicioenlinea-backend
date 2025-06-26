<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatSecretarioJuzgado extends Model
{
    //
    protected $table = 'cat_secretario_juzgado';
    protected $primaryKey = 'idCatSecretarioJuzgado';
    protected $fillable = [
        'idUsr',
        'idGeneral',
        'idCatJuzgado'
    ];

    
}
