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
        // Archiver les comptes bloqués expirés chaque jour à 2h du matin
        $schedule->job(new \App\Jobs\ArchiveExpiredBlockedAccounts)
            ->dailyAt('02:00')
            ->name('archive-expired-blocked-accounts')
            ->withoutOverlapping()
            ->runInBackground();

        // Débloquer automatiquement les comptes bloqués expirés chaque heure
        $schedule->job(new \App\Jobs\UnblockExpiredAccounts)
            ->hourly()
            ->name('unblock-expired-accounts')
            ->withoutOverlapping()
            ->runInBackground();
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