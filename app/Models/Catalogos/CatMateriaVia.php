<?php

namespace App\Models\Catalogos;

use App\Models\Catalogos\CatMaterias;
use App\Models\Catalogos\CatTipoVias;
use Illuminate\Database\Eloquent\Model;
use App\Models\PreRegistro;

class CatMateriaVia extends Model
{
    protected $table = 'cat_materia_via';
    protected $primaryKey = 'idCatMateriaVia'; // Nombre real de la clave primaria

    protected $fillable = [
        'idCatMateria',
        'idCatVia',
        'activo',
    ];

    public function catMateria()
    {
        return $this->belongsTo(CatMaterias::class, 'idCatMateria');
    }

    public function catVia()
    {
        return $this->belongsTo(CatTipoVias::class, 'idCatTipoVia');
    }

    public function preRegistros()
    {
        return $this->hasMany(PreRegistro::class, 'idCatMateriaVia');
    }
}

