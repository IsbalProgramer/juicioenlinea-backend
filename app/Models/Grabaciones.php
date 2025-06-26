<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grabaciones extends Model
{
    //
    protected $table = 'grabaciones';
    protected $primaryKey = 'idGrabacion';

    protected $fillable = [
        'idAudiencia',
        'id',
        'meetingSeriesId',
        'topic',
        'timeRecorded',
        'downloadUrl',
        'playbackUrl',
        'password',
        'durationSeconds'
    ];
    public function audiencia()
    {
        return $this->belongsTo(Audiencia::class, 'idAudiencia', 'idAudiencia');
    }
    public function solicitudes()
    {
        return $this->hasMany(Solicitudes::class, 'idGrabacion', 'idGrabacion');
    }



}
