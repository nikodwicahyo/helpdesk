<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Hourly session cleanup - remove expired sessions
        $schedule->command('sessions:cleanup --force')
            ->hourly()
            ->description('Clean up expired sessions')
            ->withoutOverlapping()
            ->runInBackground()
            ->onSuccess(function () {
                Log::info('Scheduled session cleanup completed successfully');
            })
            ->onFailure(function () {
                Log::error('Scheduled session cleanup failed');
            });
        
        // Daily deep cleanup - remove old inactive sessions (7 days+)
        $schedule->command('sessions:cleanup --days=7 --force')
            ->daily()
            ->at('02:00')
            ->description('Deep cleanup of old inactive sessions')
            ->withoutOverlapping()
            ->runInBackground();
        
        // Daily cleanup of expired ticket drafts at 2:00 AM
        $schedule->command('drafts:cleanup')
            ->daily()
            ->at('02:30')
            ->description('Clean up expired ticket drafts')
            ->onOneServer()
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/draft-cleanup.log'))
            ->onFailure(function () {
                Log::error('Draft cleanup scheduled task failed');
            });
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