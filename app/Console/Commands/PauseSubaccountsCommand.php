<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class PauseSubaccountsCommand extends Command
{
    protected $signature   = 'subaccounts:check-pause';
    protected $description = 'Check and pause subaccounts based on pause_at date';

    public function handle()
    {
        $today = Carbon::now()->startOfDay();

        $subaccounts = UserSetting::whereNotNull('pause_at')
            ->where('pause_at', '<=', $today)
            ->where('paused', 0)
            ->get();

        foreach ($subaccounts as $subaccount) {
            PauseSubaccountJob::dispatch($subaccount);
        }

        $this->info('Subaccounts checked for pause');
    }
}
