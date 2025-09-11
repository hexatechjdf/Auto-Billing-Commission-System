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
        // $schedule->command('inspire')->hourly();
        // $schedule->job(new \App\Jobs\SyncGhlPricesJob)->daily();

        // $schedule->command('subaccount:process-backups')->daily(); // TODO: may be set cron on server insted of here in schedule

        // Run every 5 days at 12:10 AM
        // $schedule->command('orders:process')->cron('10 0 */5 * *');

        // $schedule->job(new \App\Jobs\ProcessMonthlyInvoicesJob)->dailyAt('02:00');
        // $schedule->job(new \App\Jobs\PauseSubaccountsJob)->daily();

    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
