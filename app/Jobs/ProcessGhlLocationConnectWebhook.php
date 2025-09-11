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

class ProcessGhlLocationConnectWebhook implements ShouldQueue
{
    //NOt in use
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $type       = $this->payload['installType'] ?? null;
        $locationId = $this->payload['locationId'] ?? null;
        $companyId  = $this->payload['companyId'] ?? null;

        if ($type != 'Location') {

            return;
        }
        // TODO: I thing there should be the whole process like in locationCreate

        try {
            $companyToken  = CRM::getCrmToken(['company_id' => $companyId, 'user_type' => CRM::$lang_com]); //this is also managing the cache data
            $companyUserId = $companyToken->user_id ?? null;

            if (! $companyUserId) {
                Log::error('No user found for company ID', ['companyId' => $companyId]);
                return;
            }

// dd($companyUserId, $locationId);
// Connect subaccount
            $token = CRM::getLocationAccessToken($companyUserId, $locationId, $companyToken, 0, $dbUserId);

            if ($token) {
                Log::info('Connected subaccount for location', ['locationId' => $locationId, 'tokenData' => $token]);
            } else {
                Log::error('Failed to connect subaccount', ['locationId' => $locationId, 'tokenData' => $token]);
                // return;
            }

        } catch (\Throwable $th) {

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
