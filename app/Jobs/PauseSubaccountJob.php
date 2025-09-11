<?php
namespace App\Jobs;

use App\Helper\CRM;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PauseSubaccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $subaccount;

    public function __construct(UserSetting $subaccount, $forcePause = false) // TODO: manage force paused param
    {
        $this->subaccount = $subaccount;
    }

    public function handle()
    {
        $subaccount = $this->subaccount;

        $locationId = $subaccount->location_id;
        $userId     = $subaccount->user_id;

        $response = CRM::crmV2Loc($userId, $locationId, 'saas-api/public-api/pause/' . $locationId, 'POST');

        if (isset($response->success)) { //TODO confirm this condition based on response
            // $subaccount->paused = 1;
            // $subaccount->save();

            $subaccount->update(['paused' => 1]);

            Log::info('Subaccount paused successfully', ['locationId' => $locationId]);
        } else {
            Log::error('Failed to pause subaccount', ['locationId' => $locationId, 'response' => $response]);
        }
    }
}
