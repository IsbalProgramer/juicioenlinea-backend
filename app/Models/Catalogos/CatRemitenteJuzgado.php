<?php

namespace App\Models\Catalogos;

use Illuminate\Database\Eloquent\Model;

class CatRemitenteJuzgado extends Model
{
    protected $table = 'cat_remitente_juzgados';
    protected $primaryKey = 'idCatRemitenteJuzgado';
    protected $fillable = [
        'idCatJuzgado',
        'idCatRemitente',
    ];

    public function juzgado()
    {
        return $this->belongsTo(CatJuzgados::class, 'idCatJuzgado', 'IdCatJuzgado');
    }

    public function remitente()
    {
        return $this->belongsTo(CatRemitente::class, 'idCatRemitente');
    }
}
