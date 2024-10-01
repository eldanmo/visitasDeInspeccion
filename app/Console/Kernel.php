<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Http\Controllers\DiagnosticoController;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {   
        $controller = new DiagnosticoController();
        $schedule->call([$controller, 'actualizar_dias_visitas'])->weekdays()->dailyAt('8:00');
        $schedule->call([$controller, 'actualizar_historico_visitas'])->weekdays()->dailyAt('23:59');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
