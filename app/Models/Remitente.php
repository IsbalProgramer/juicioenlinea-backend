<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Remitente extends Model
{
    protected $primaryKey = 'idRemitente';

    protected $fillable = [
        'idTramite',
        'categoria',
        'depedencia',
        'remitente',
        'cargo'
    ];
}
