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

//use App\Jobs\ConnectSubaccountJob;

class SyncGhlSubaccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // protected $location;

    public function __construct(protected $location)
    {
        // $this->location = $location;
    }

    public function handle()
    {
        try {

            $subaccont = $this->location;

            $locationId = $subaccont->id;
            $companyId  = $subaccont->companyId;

            if (! $locationId || ! $companyId) {
                // Log::error('Missing required fields for this location ', ['locationId' => $locationId]);
                return;
            }

            list($isNewlyCreated, $dbUser) = findOrCreateUserInDb($locationId);

            if (! $dbUser) {
                Log::error('Uses not found or created agaist this location', ['locationId' => $locationId]);
                return;
            }

            $dbUserId = $dbUser->id;

            // dd($dbUserId, $isNewlyCreated);
            // \gCache::del('loc_iH97EKkQZYraKdjvKRN0');
            // \gCache::del('loc_HuVkfWx59Pv4mUMgGRTp');

            // use CRM::getCrmToken because its handling cache mecanisim
            // dd($isNewlyCreated, $dbUserId, CRM::getCrmToken(['location_id' => $locationId]));

            if ($isNewlyCreated || ! $locationUserId = CRM::getCrmToken(['location_id' => $locationId])?->user_id) { // check if isNewlyCreated or crmToken not exist for this location then also getLocationAccessToken

                ConnectSubaccountJob::dispatch($companyId, $locationId, $dbUserId);
            }

            $userSetting = UserSetting::firstWhere('user_id', $dbUserId);

            if (! $userSetting) {
                $email = $subaccont->email ?? $locationId . '@syncSubaccount.com';

                $userSetting = UserSetting::create(
                    $this->prepareUserSettingDataFromLocation($dbUserId, $locationId, $email, $subaccont)
                );

                Log::info('Created user setting for location', ['locationId' => $locationId, 'email' => $email]);
            }

            Log::info('Subaccount synced successfully', ['location_id' => $locationId]);
        } catch (\Exception $e) {
            Log::error('Failed to sync subaccount', [
                'location_id' => $locationId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Prepare UserSetting data structure
     */
    private function prepareUserSettingDataFromLocation(int $dbUserId, string $locationId, ?string $email, $subaccountData): array
    {
        return [
            'user_id'          => $dbUserId,
            'location_id'      => $locationId,
            'location_name'    => data_get($subaccountData, 'name'),
            'email'            => $email,
            'threshold_amount' => 0.00,
            'currency'         => 'USD',
        ];
    }
}
