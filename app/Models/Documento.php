<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Requerimiento;
class Documento extends Model
{
    protected $table = 'documentos'; // Nombre real de la tabla en la BD
    protected $primaryKey = 'idDocumento'; // Nombre real de la clave primaria

    public $incrementing = true; // Asegurar que Laravel sepa que la clave es auto-incremental
    protected $keyType = 'int'; // Definir el tipo de dato de la clave primaria

    protected $fillable = ['idExpediente', 'folio', 'nombre', 'documento'];


    public function requerimiento(){
        return $this->belongsTo(Requerimiento::class, 'idDocumento');
    }

    public function inicio(){
        return $this->belongsTo(Inicio::class, 'idInicio');
    }

}



