<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    //Clase documento
    protected $table = 'documentos';
    
    protected $fillable = [
        'idExpediente',
        'folioPreregistro',
        'nombre',
        'documento'
    ];
    
}
