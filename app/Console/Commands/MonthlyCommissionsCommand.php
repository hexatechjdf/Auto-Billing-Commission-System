<?php
namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;

class MonthlyCommissionsCommand extends Command
{
    protected $signature   = 'commissions:monthly';
    protected $description = 'Process monthly commissions if first day of month';

    public function handle()
    {
        if (Carbon::now()->day === 1) {
            ProcessMonthlyCommissionsJob::dispatch();
            $this->info('Monthly commissions job dispatched');
        } else {
            $this->info('Not the first day of the month, skipping');
        }
    }
}
