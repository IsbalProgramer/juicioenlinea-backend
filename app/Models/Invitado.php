<?php

namespace App\Models;

use App\Models\Catalogos\CatSexos;
use App\Models\Catalogos\CatTipoPartes;
use Illuminate\Database\Eloquent\Model;

class Invitado extends Model
{
    //
    protected $table = 'invitados';
    protected $primaryKey = 'idInvitado';
    protected $fillable = [
        'idAudiencia',
        'idUsr',
        'idCatSexo',
        'idCatTipoParte',
        'correo',
        'correoAlterno',
        'nombre',
        'coHost',
        'direccion',
        'esAbogado'
    ];
    public $timestamps = true;

    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class, 'idAudiencia', 'idAudiencia');
    }
    public function catTipoParte()
    {
        return $this->belongsTo(CatTipoPartes::class, 'idCatTipoParte', 'idCatTipoParte');
    }
    public function catSexo()
    {
        return $this->belongsTo(CatSexos::class, 'idCatSexo');
    }
}
