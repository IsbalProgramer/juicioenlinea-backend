<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remitente extends Model
{
     protected $table = 'cat_remitentes';
    protected $primaryKey = 'idCatRemitente';

    protected $fillable = [
        'idTramite',
        'categoria',
        'depedencia',
        'remitente',
        'cargo'
    ];
}
