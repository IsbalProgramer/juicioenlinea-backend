<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
//     //Clase documento

//     protected $table = 'documentos';

//     protected $primaryKey = 'idDocumento';

//     protected $fillable = ['idExpediente', 'folio', 'nombre', 'documento'];

//    // Convierte automáticamente Base64 a string
//    protected $casts = [
//     'documento' => 'string',
// ];

//     public function Requerimiento()
//     {
//         return $this->belongsTo(Requerimiento::class);
//     }

// }

    protected $table = 'documentos'; // Nombre real de la tabla en la BD
    protected $primaryKey = 'idDocumento'; // Nombre real de la clave primaria

    public $incrementing = true; // Asegurar que Laravel sepa que la clave es auto-incremental
    protected $keyType = 'int'; // Definir el tipo de dato de la clave primaria

    protected $fillable = [
        'nombre', 'folio', 'idExpediente', 'documento'
    ];
}



