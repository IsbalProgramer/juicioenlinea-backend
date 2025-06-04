<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invitado extends Model
{
    //
    protected $table = 'invitados';
    protected $primaryKey = 'idInvitado';
    protected $fillable = [
        'idAudiencia',
        'email',
        'displayName',
        'coHost',
    ];
    public $timestamps = true;

    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class, 'idAudiencia', 'idAudiencia');
    }
}
