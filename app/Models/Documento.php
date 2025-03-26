<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento extends Model
{
    //Clase documento

    protected $table = 'documentos';

    protected $primaryKey = 'idDocumento';

    protected $fillable = ['idExpediente', 'folio', 'nombre', 'documento'];

   // Convierte automáticamente Base64 a string
   protected $casts = [
    'documento' => 'string',
];

    public function Requerimiento()
    {
        return $this->belongsTo(Requerimiento::class);
    }
}
