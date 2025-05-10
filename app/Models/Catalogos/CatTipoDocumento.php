<?php

namespace App\Models\Catalogos;

use App\Models\Documento;
use Illuminate\Database\Eloquent\Model;

class CatTipoDocumento extends Model
{
    protected $table = 'cat_tipo_documentos';
    protected $primaryKey = 'idCatTipoDocumento'; // Nombre real de la clave primaria

    protected $fillable = [
        'descripcion',
        'requiereEscaneo',
        'activo',
    ];

    /**
     * Relación con Documento
     */
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'idCatTipoDocumento');
    }


}
