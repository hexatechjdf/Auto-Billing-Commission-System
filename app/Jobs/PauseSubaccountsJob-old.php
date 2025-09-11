<?php
namespace App\Jobs;

use App\Helper\CRM;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PauseSubaccountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $today = Carbon::now()->format('Y-m-d');

        $subaccountsToPause = UserSetting::whereDate('pause_at', $today)->where('paused', 0)->get();

        foreach ($subaccountsToPause as $userSetting) {
            $locationId = $userSetting->location_id;

            $response = CRM::makeCall("saas-api/public-api/pause/{$locationId}", 'POST', null, [], true);

            if ($response && ! self::isExpired($response)) {
                $userSetting->update(['paused' => 1]);
                Log::info('Paused subaccount', ['locationId' => $locationId]);
            } else {
                Log::error('Failed to pause subaccount', ['locationId' => $locationId, 'response' => $response]);
            }
        }

        Log::info('Pause subaccounts processed successfully');
    }
}
