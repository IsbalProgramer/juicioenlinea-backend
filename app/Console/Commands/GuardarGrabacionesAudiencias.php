<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Audiencia;
use App\Http\Controllers\GrabacionesController;
use App\Services\MeetingService;
use Carbon\Carbon;
class GuardarGrabacionesAudiencias extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audiencias:guardar-grabaciones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Consulta y guarda grabaciones de audiencias finalizadas del día de hoy';

    /**
     * Execute the console command.
     */
    public function handle(MeetingService $meetingService)
    {
        $hoy = Carbon::today();
        $audiencias = Audiencia::whereDate('start', $hoy)
            ->whereHas('historialEstados', function ($q) {
                $q->where('idCatalogoEstadoAudiencia', 2)
                  ->where('fechaHora', '<=', Carbon::now()->subHour());
            })
            ->get();

        $grabacionesController = new GrabacionesController();

        foreach ($audiencias as $audiencia) {
            $grabacionesController->store($audiencia->idAudiencia, $meetingService);
        }

        $this->info('Grabaciones consultadas y guardadas para audiencias finalizadas de hoy.');
    }
}
