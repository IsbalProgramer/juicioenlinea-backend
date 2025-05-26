<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expediente extends Model
{
    protected $table = 'expedientes'; // Nombre de la tabla
    protected $primaryKey = 'idExpediente'; // Clave primaria
    public $timestamps = false;
    protected $fillable = [
        'idPreregistro',
        'NumExpediente',
        'idCatJuzgado',
        'fechaResponse',
        'idSecretario'
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
    public function preRegistro()
    {
        return $this->belongsTo(PreRegistro::class, 'idPreregistro');
    }
    public function requerimientos()
    {
        return $this->hasMany(Requerimiento::class, 'idExpediente');
    }
}
