<?php
namespace App\Jobs;

use App\Helper\CRM;
// use App\Jobs\ConnectSubaccountJob;
use App\Models\UserSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessGhlLocationWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $type       = $this->payload['type'] ?? null;
        $locationId = $this->payload['id'] ?? $payload['locationId'] ?? null;
        $email      = $this->payload['email'] ?? null;
        $companyId  = $this->payload['companyId'] ?? null;

        if (! $locationId || ! $companyId) {
            Log::error('Invalid GHL location webhook payload', ['payload' => $this->payload]);
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

        // using CRM::getCrmToken because its handling cache mecanisim

                                                                                                                // dd($isNewlyCreated, $dbUserId, CRM::getCrmToken(['location_id' => $locationId]));
        if ($isNewlyCreated || ! $locationUserId = CRM::getCrmToken(['location_id' => $locationId])?->user_id) { // check if isNewlyCreated or crmToken not exist for this location then also getLocationAccessToken
            ConnectSubaccountJob::dispatch($companyId, $locationId, $dbUserId);
        }

        $userSetting = UserSetting::firstWhere('user_id', $dbUserId);

        $subaccountDataKey    = getSubaccountDataCacheKey($email);
        $subaccountCachedData = $email ? Cache::get($subaccountDataKey, []) : []; // or key will be orderStatus_email // review it

        // Handle location App INSTALL
        if ($type === 'INSTALL') {
            if (! $userSetting) {
                $userSetting = UserSetting::create(
                    $this->prepareUserSettingData($dbUserId, $locationId, $email, $subaccountCachedData)
                );

                Log::info('Created user setting When INSTALL Webhook received for location', ['locationId' => $locationId, 'email' => $email]);
            }
            return;
        }

        // Handle Location Create
        if ($type === 'LocationCreate') {
            if (! $userSetting) {
                $userSetting = UserSetting::create(
                    $this->prepareUserSettingData($dbUserId, $locationId, $email, $subaccountCachedData)
                );

                if ($email) {
                    Cache::forget($subaccountDataKey);
                }

                Log::info('Created user setting for location', ['locationId' => $locationId, 'email' => $email]);
            }
            return;
        }

                                                                                            //  Handle Location Update (only if email was missing before)
        if ($type === 'LocationUpdate' && $email && $userSetting && ! $userSetting->email) { //TODO
            $userSetting->update(
                $this->prepareUserSettingData($dbUserId, $locationId, $email, $subaccountCachedData)
            );

            Cache::forget($subaccountDataKey);

            Log::info('Updated email for user setting', ['locationId' => $locationId, 'email' => $email]);
        }

    }

    /**
     * Prepare UserSetting data structure
     */
    private function prepareUserSettingData(int $dbUserId, string $locationId, ?string $email, array $subaccountData): array
    {
        return [
            'user_id'                  => $dbUserId,
            'location_id'              => $locationId,
            'email'                    => $email,
            'stripe_payment_method_id' => data_get($subaccountData, 'stripeData.stripe_payment_method_id'),
            'stripe_customer_id'       => data_get($subaccountData, 'stripeData.stripe_customer_id'),
            'contact_id'               => data_get($subaccountData, 'orderData.contactId'),
            'threshold_amount'         => data_get($subaccountData, 'priceMappingData.threshold_amount'),
            'currency'                 => data_get($subaccountData, 'priceMappingData.currency'),
            'price_id'                 => data_get($subaccountData, 'priceMappingData.price_id'),
            'amount_charge_percent'    => data_get($subaccountData, 'priceMappingData.amount_charge_percent', 2.0),
        ];
    }
}
