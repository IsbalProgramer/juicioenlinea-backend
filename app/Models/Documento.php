<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    protected $table = 'documentos'; // Nombre real de la tabla en la BD
    protected $primaryKey = 'idDocumento'; // Nombre real de la clave primaria

    public $incrementing = true; // Asegurar que Laravel sepa que la clave es auto-incremental
    protected $keyType = 'int'; // Definir el tipo de dato de la clave primaria

    protected $primaryKey = 'idDocumento';

    protected $fillable = ['idExpediente', 'folio', 'nombre', 'documento'];


    public function documento(){
        return $this->belongsTo(Documento::class, 'idDocumento');
    }
}



