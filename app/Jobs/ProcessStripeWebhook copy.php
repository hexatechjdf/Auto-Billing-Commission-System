<?php
namespace App\Jobs;

use App\Models\UserSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessStripeWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    // =========== Copy one ==================

    public function handle()
    {
        $type       = $this->payload['type'] ?? null;
        $metadata   = $this->payload['data']['object']['metadata'] ?? [];
        $locationId = $metadata['locationId'] ?? $metadata['contactId'] ?? null;

        if (! $locationId) {
            Log::error('Missing locationId or contactId in Stripe webhook metadata', ['payload' => $this->payload]);
            return;
        }

        $stripeData = [];
        if ($type === 'customer.created') {
            $stripeData['stripe_customer_id'] = $this->payload['data']['object']['id'] ?? null; // may be payment_method_id is also available in this webhook like ( $this->payload['data']['object']['invoice_settings']['default_payment_method'])
        } elseif ($type === 'payment_intent.succeeded') {
            // $stripeData['stripe_customer_id'] = $this->payload['data']['object']['customer'] ?? null;
            $stripeData['stripe_payment_method_id'] = $this->payload['data']['object']['payment_method'] ?? null;
        }

        // case 'customer.created':                                                                //// TODO: maybe dont need to listen 'customer.created' webhook
//     $stripeData = ['stripe_customer_id' => $dataObject['id'] ?? null]; // // may be payment_method_id is also available in this webhook like ( $dataObject['invoice_settings']['default_payment_method'])
//     break;

        if (empty($stripeData)) {
            Log::error('No relevant data in Stripe webhook', ['payload' => $this->payload]);
            return;
        }

        $userSetting = UserSetting::where('location_id', $locationId)->first();
        // $userSetting = UserSetting::where('stripe_payment_method_id', $stripeData['stripe_payment_method_id'])->get(); // TODO: where payment_method_id  and use get()

        if ($userSetting) {
            $userSetting->update($stripeData);
            Log::info('Updated user setting with Stripe data', ['locationId' => $locationId, 'data' => $stripeData]);
        } else {
            Cache::put("stripe_data_{$locationId}", $stripeData, now()->addMinutes(10));
            Log::info('Cached Stripe data for location', ['locationId' => $locationId, 'data' => $stripeData]);

            // $updatedRows = UserSetting::where('contact_id', $contactId)
            //     ->update($stripeData);

            // if ($updatedRows == 0) {

            //     $stripeDataKey = getStripeDataCacheKey($contactId);
            //     Cache::put($stripeDataKey, $stripeData, now()->addMinutes(10));
            // }

            $stripeDataKey = getStripeDataCacheKey($contactId);
            Cache::put($stripeDataKey, $stripeData, now()->addMinutes(20));

            Log::info('Cached Stripe data for contact', ['contactId' => $contactId, 'stripeData' => $stripeData]);

        }
    }
}
