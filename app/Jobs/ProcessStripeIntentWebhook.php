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

class ProcessStripeIntentWebhook implements ShouldQueue
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
        $dataObject = $this->payload['data']['object'];
        $metadata   = $dataObject['metadata'] ?? [];
        $contactId  = $metadata['contactId'] ?? null;

        if (! $contactId) {
            Log::error('Missing contactId in Stripe webhook metadata', ['payload' => $this->payload]);
            return;
        }

        switch ($type) {
            case 'payment_intent.succeeded':

                $customerId      = $dataObject['customer'] ?? null;
                $paymentMethodId = $dataObject['payment_method'] ?? null;

                $stripeData = [
                    'stripe_customer_id'       => $customerId,
                    'stripe_payment_method_id' => $paymentMethodId,
                ];
                break;
            default:
                Log::info('Unhandled Stripe webhook type', ['type' => $type, 'payload' => $this->payload]);
                return;
        }

        // if (empty($stripeData)) {
        //     Log::error('No relevant data in Stripe webhook', ['payload' => $this->payload]);
        //     return;
        // }

        $updatedRows = UserSetting::Where('contact_id', $contactId)
            ->update($stripeData);

        if ($updatedRows > 0) { // when attached, dettached
            Log::info('Updated user setting with Stripe data', ['contactId' => $contactId, 'stripeData' => $stripeData]);
        } else { // when payment_intent.succeeded

            $stripeDataKey = getStripeDataCacheKey($contactId);
            Cache::put($stripeDataKey, $stripeData, now()->addMinutes(20));

            Log::info('Cached Stripe data for contact', ['contactId' => $contactId, 'stripeData' => $stripeData]);
        }
    }
}
