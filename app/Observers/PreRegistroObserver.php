<?php

namespace App\Observers;

use App\Models\PreRegistro;
use Illuminate\Support\Facades\Log;

class PreRegistroObserver
{
    /**
     * Handle the PreRegistro "created" event.
     */
    public function created(PreRegistro $preRegistro): void
    {
        //
    }

    /**
     * Handle the PreRegistro "updated" event.
     */
    public function updated(PreRegistro $preRegistro)
    {
        Log::info('Observer PreRegistro actualizado', [
            'id' => $preRegistro->idPreregistro,
            'cambios' => $preRegistro->getDirty()
        ]);
        // Verifica si alguno de los campos cambió
        if (
            $preRegistro->isDirty('idCatJuzgado') ||
            $preRegistro->isDirty('idExpediente') ||
            $preRegistro->isDirty('fechaResponse')
        ) {
            $preRegistro->historialEstado()->create([
                'idCatEstadoInicio' => 2,
                'fechaEstado' => now(),
            ]);
        }
    }

    /**
     * Handle the PreRegistro "deleted" event.
     */
    public function deleted(PreRegistro $preRegistro): void
    {
        //
    }

    /**
     * Handle the PreRegistro "restored" event.
     */
    public function restored(PreRegistro $preRegistro): void
    {
        //
    }

    /**
     * Handle the PreRegistro "force deleted" event.
     */
    public function forceDeleted(PreRegistro $preRegistro): void
    {
        //
    }
}
