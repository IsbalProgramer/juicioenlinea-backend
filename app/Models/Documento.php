<?php

namespace App\Models;

use App\Models\Catalogos\CatTipoDocumento;
use Illuminate\Database\Eloquent\Model;
use App\Models\Requerimiento;

class Documento extends Model
{
    protected $table = 'documentos'; // Nombre real de la tabla en la BD
    protected $primaryKey = 'idDocumento'; // Nombre real de la clave primaria

    public $incrementing = true; // Asegurar que Laravel sepa que la clave es auto-incremental
    protected $keyType = 'int'; // Definir el tipo de dato de la clave primaria

    protected $fillable = [
        'idPreregistro',
        'idCatTipoDocumento',
        'nombre',
        'documento',
    ];


    public function requerimientos()
    {
        return $this->belongsToMany(Requerimiento::class, 'documento_requerimiento', 'idDocumento', 'idRequerimiento');
    }
    /**
     * Relación con PreRegistro
     */
    public function preregistro()
    {
        return $this->belongsTo(PreRegistro::class, 'idPreregistro');
    }
    /**
     * Relación con CatTipoDocumento
     */
    public function catTipoDocumento()
    {
        return $this->belongsTo(CatTipoDocumento::class, 'idCatTipoDocumento');
    }
}
