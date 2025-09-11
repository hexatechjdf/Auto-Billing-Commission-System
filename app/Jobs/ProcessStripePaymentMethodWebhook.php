<?php
namespace App\Jobs;

use App\Models\UserSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessStripePaymentMethodWebhook implements ShouldQueue
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

        switch ($type) {
            case 'payment_method.attached':

                $customerId      = $dataObject['customer'] ?? null;
                $paymentMethodId = $dataObject['id'] ?? null;

                $stripeData = [
                    'stripe_customer_id'       => $customerId,
                    'stripe_payment_method_id' => $paymentMethodId,
                ];
                break;
            case 'payment_method.detached':

                $customerId      = $this->payload['data']['previous_attributes']['customer'] ?? null;
                $paymentMethodId = $dataObject['id'] ?? null;

                // TODO: when detached trigger the job with 1 hour delay. then in the job check if still stripe_customer_id = null  then call the GHL's API "subaccount pause" in a separete background job (with param forcePause= true/false (default false) because if forcePaused then we don't need to check the stripe_cusomer_id = null, if forcePaused = false then stripe_cusomer_id should must be null).
                $stripeData = [
                    'stripe_customer_id'       => null,
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

        $updatedRows = UserSetting::where('stripe_customer_id', $customerId)
            ->update($stripeData);

        if ($updatedRows > 0) { // when attached, dettached
            Log::info('Updated stripeData in userSetting agaist this stripe_customer_id', ['stripe_customer_id' => $customerId, 'stripeData' => $stripeData]);
        }
    }
}
