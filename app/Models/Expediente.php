<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Expediente extends Model
{
    protected $table = 'expediente';
    protected $primaryKey = 'idExpediente';
    protected $fillable = [
        'sintesis',
        'folio_preregistro',
        'archivado',
        'idUsuario',

    ];


    /**
     * Get the inicio that owns the Expediente
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function inicio(): BelongsTo
    {
        return $this->belongsTo(Inicio::class);
    }
    

}
