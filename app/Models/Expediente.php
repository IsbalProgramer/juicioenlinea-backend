<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Expediente extends Model
{
    protected $table = 'expedientes'; // Nombre de la tabla
    protected $primaryKey = 'idExpediente'; // Clave primaria
    protected $fillable = [
        'sintesis',
        'folio_preregistro',
        'archivado',
        'idUsuario',
    ];

    /**
     * Relación con el modelo Inicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

        public function tramites()
    {
        return $this->hasMany(Tramite::class, 'idExpediente');
    }
}
