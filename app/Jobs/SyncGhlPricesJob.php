<?php
namespace App\Jobs;

use App\Services\PlanMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncGhlPricesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        //TODO: Test it
        $primarySubaccount = supersetting('primary_subaccount');
        if (! $primarySubaccount) {
            $this->info('No primary subaccount set. Skipping sync.');
            return;
        }

        $planMappingService = app(PlanMappingService::class);

        $response = $planMappingService->fetchInventories($primarySubaccount); // TODO: if used this job get the admin user in fetchInventories instead of login user
        if (! $response['status']) {
            $this->error("Failed to sync for primary subaccount $primarySubaccount: " . $response['message']);
            return;
        }

        $inventories = $response['inventories'];

        $planMappingService->syncPlanMappingsForLocation($primarySubaccount, $inventories);

        $this->info("Synced prices for primary subaccount: $primarySubaccount");
    }
}
