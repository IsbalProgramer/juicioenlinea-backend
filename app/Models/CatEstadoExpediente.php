<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CatEstadoExpediente extends Model
{
    protected $table = 'cat_estado_expediente';
    protected $primaryKey = 'idEstadoExpediente';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'idEstadoExpediente',
        'clave',
        'descripcion',
    ];
}
