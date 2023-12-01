<?php

namespace App\Console;

use App\Console\Commands\ParseLinksCommand;
use App\Console\Commands\ParseSitesCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(ParseLinksCommand::class)->everyMinute();
        $schedule->command(ParseSitesCommand::class)->everyMinute();

        $schedule->call(function () {
            Artisan::call('horizon:snapshot');
            Log::debug('horizon:snapshot');
        })->everyFiveMinutes();

    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
