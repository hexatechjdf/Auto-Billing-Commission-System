<?php
namespace App\Jobs;

use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessMonthlyCommissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $carbonNow        = Carbon::now();
        $startOfLastMonth = $carbonNow->copy()->subMonth()->startOfMonth();
        $endOfLastMonth   = $carbonNow->copy()->subMonth()->endOfMonth();
        $today            = $carbonNow->copy()->startOfDay();

        // Get all active subaccounts
        $subaccounts = UserSetting::where(['chargeable' => 1, 'paused' => 0])->get();

        foreach ($subaccounts as $subaccount) {

            ProcessMonthlyCommissionsChargeJob::dispatchSync([ // TODO: remove Sync
                'subaccount'       => $subaccount,
                'carbonNow'        => $carbonNow,
                'startOfLastMonth' => $startOfLastMonth,
                'endOfLastMonth'   => $endOfLastMonth,
                'today'            => $today,

            ]);
        }
    }
}
