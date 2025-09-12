<?php
namespace App\Jobs;

use App\Helper\CRM;
use App\Models\UserSetting;
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

    public function __construct(UserSetting $subaccount, protected $paused = true) // TODO: manage force paused param
    {
        $this->subaccount = $subaccount;
    }

    public function handle()
    {
        $subaccount = $this->subaccount;
        $paused     = $this->paused;

        $locationId = $subaccount->location_id;
        $userId     = $subaccount->user_id;

        $companyId = CRM::getCrmToken(['location_id' => $locationId])?->company_id;

        $companyToken = CRM::getCrmToken([
            'company_id' => $this->companyId,
            'user_type'  => CRM::$lang_com,
        ]);

        $data = ['paused' => $paused, 'companyId' => $companyId];

        // saas-api/public-api/pause/

        $response = CRM::agencyV2($companyToken->user_id, 'saas/pause/' . $locationId, 'POST', $data, [], true, $companyToken);

        if (isset($response->message) && $response->message == 'success') {

            $subaccount->update(['paused' => $paused]);

            Log::info('Subaccount paused successfully', ['locationId' => $locationId]);
        } else {
            Log::error('Failed to pause subaccount', ['locationId' => $locationId, 'response' => $response]);
        }
    }
}
