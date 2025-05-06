<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\PreRegistro;

class CatMateriaVia extends Model
{
    protected $table = 'cat_materia_via';
    protected $primaryKey = 'idCatMateriaVia'; // Nombre real de la clave primaria

    protected $fillable = [
        'idCatMateria',
        'idCatVia',
    ];

    public function catMateria()
    {
        return $this->belongsTo(\App\Models\Catalogos\catMaterias::class, 'idCatMateria');
    }

    public function catVia()
    {
        return $this->belongsTo(\App\Models\Catalogos\catVias::class, 'idCatVia');
    }

    public function preRegistros()
    {
        return $this->hasMany(PreRegistro::class, 'idCatMateriaVia');
    }
}
