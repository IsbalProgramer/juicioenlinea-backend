<?php

namespace App\Models;

use App\Models\Catalogos\CatMateriaVia;
use App\Models\Parte;
use App\Models\Documento;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PreRegistro extends Model
{
    
    protected $table = 'pre_registros';
    protected $primaryKey = 'idPreregistro';
    protected $fillable = [
        'folioPreregistro',
        'idCatMateriaVia',
        'sintesis',
        'observaciones',
        'fechaCreada',
        'idGeneral',
        'idCatJuzgado',
        'idExpediente',
    ];

    public function catMateriaVia()
    {
        return $this->belongsTo(CatMateriaVia::class, 'idCatMateriaVia');
    }
    public function partes()
    {
        return $this->hasMany(Parte::class, 'idPreregistro');
    }
    public function documentos()
    {
        return $this->hasMany(Documento::class, 'idPreregistro');
    }
    public function historialEstado()
    {
        return $this->hasMany(HistorialEstadoInicio::class, 'idPreregistro');
    }
    public function expediente()
    {
        return $this->hasOne(Expediente::class);
    }


}
