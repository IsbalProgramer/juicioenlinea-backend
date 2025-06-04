<?php

namespace App\Models\Catalogos;

use App\Models\Audiencia;
use Illuminate\Database\Eloquent\Model;

class CatEstadoAudiencia extends Model
{
    // Clase catalogo Estado Audiencia
    protected $table = 'cat_estado_audiencias';
    protected $primaryKey = 'idCatalogoEstadoAudiencia';
    protected $fillable = [
        'descripcion',
        'activo'
    ];

    public $timestamps = false;

    public function audiencias()
    {
        return $this->hasMany(Audiencia::class, 'idCatalogoEstadoAudiencia', 'idCatalogoEstadoAudiencia');
    }
}