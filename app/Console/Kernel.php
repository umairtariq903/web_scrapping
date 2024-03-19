<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:organic_traveller')->everyMinute();
        $schedule->command('app:fetch-data-from-ilaam')->everyMinute();
        $schedule->command('app:fetch-data-from-zah-computers')->everyMinute();
        $schedule->command('app:fetch-data-eezepc')->everyMinute();
        $schedule->command('app:fetch-from-rebeltech')->everyMinute();
        $schedule->command('app:fetch-data-alfatah')->everyMinute();
        $schedule->command('app:fetch-from-czone')->everyMinute();
        $schedule->command('app:fetch-data-from-high-pk')->everyMinute(); // Adjust the schedule as per your requirements
        $schedule->command('app:fetch-data-from-brands-corner')->everyMinute(); // Adjust the schedule as per your requirements
        // Adjust the schedule as per your requirements
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
