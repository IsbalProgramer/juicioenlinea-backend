<?php

namespace App\Models;

use App\Models\Catalogos\CatMaterias;
use App\Models\Catalogos\CatVias;
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
        return $this->belongsTo(CatMaterias::class, 'idCatMateria');
    }

    public function catVia()
    {
        return $this->belongsTo(CatVias::class, 'idCatVia');
    }

    public function preRegistros()
    {
        return $this->hasMany(PreRegistro::class, 'idCatMateriaVia');
    }
}
