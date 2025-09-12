<?php
namespace App\Console\Commands;

use App\Jobs\ProcessPendingOrdersJob;
use Illuminate\Console\Command;

class ProcessOrdersCommand extends Command
{
    protected $signature   = 'orders:process';
    protected $description = 'Process pending orders and create transactions every 5 days';

    public function handle(): int
    {
        // ProcessPendingOrdersJob::dispatch();
        // $this->info('Pending orders processing job dispatched successfully.');

        return Command::SUCCESS;
    }
}
