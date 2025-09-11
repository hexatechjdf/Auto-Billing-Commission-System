<?php
namespace App\Jobs;

use App\Models\SubaccountDataBackup;
use App\Models\UserSetting;
use App\Services\PlanMappingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProcessPrimaryOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;
    protected $attempts = 0;

    public function __construct(array $payload, $attempts = 0)
    {
        $this->payload  = $payload;
        $this->attempts = $attempts;
    }

    public function handle(PlanMappingService $planMappingService)
    {
        $locationId = $this->payload['locationId'] ?? null;
        $contactId  = $this->payload['contactId'] ?? null;
        $email      = data_get($this->payload, 'contactSnapshot.email');

        // -------TODO:also save these in sucaccoint setting (for use in GHLInvoice generation) and also listen the contactUpdate Webhook
        $firstName = data_get($this->payload, 'contactSnapshot.firstName');
        $lastName  = data_get($this->payload, 'contactSnapshot.lastName');
        $phone     = data_get($this->payload, 'contactSnapshot.phone');
        //-------------------------------------------------------------------------------

        // dd('inJOb', $locationId, $contactId, $email);

        if (! $locationId || ! $contactId || ! $email) {
            Log::error('Missing required data in primary order payload', ['payload' => $this->payload]);
            return;
        }

        $stripeDataKey = getStripeDataCacheKey($contactId);
        $stripeData    = Cache::get($stripeDataKey);

        // dd($stripeData);

        // if (! $stripeData) {        // TODO: I think we don't need to retry when no $stripeData because we are saving contactId in subaccounts, so based on contactId we are managing
        //     if ($this->attempts < 5) { // Retry up to 5 times
        //         $this->release(120);       // Release job to retry after 2 minutes
        //         Log::error('retries job, because Stripe data not found for contactId', ['contactId' => $contactId]);
        //         return;
        //     } else {
        //         Log::error('Stripe data not found after retries for contactId', ['contactId' => $contactId]);
        //         return;
        //     }
        // }

        $subaccountData               = [];
        $subaccountData['stripeData'] = $stripeData;

        $priceIds = collect($this->payload['items'] ?? [])->pluck('price._id')->toArray();

        // $priceMapping = $planMappingService->getAllPlanMappings()->whereIn('price_id', $priceIds)->first()?->toArray(); //->keyBy('price_id')->toArray();

        $firstPriceMapping = $planMappingService
            ->getPlanMapping()
            ->select('threshold_amount', 'currency', 'price_id', 'amount_charge_percent')
            ->whereIn('price_id', $priceIds)
            ->first()?->toArray();

        $priceMappingData = [
            'threshold_amount'      => $firstPriceMapping['threshold_amount'] ?? null,
            'currency'              => $firstPriceMapping['currency'] ?? null,
            'price_id'              => $firstPriceMapping['price_id'] ?? null, // TODO: manybe need to save the primary id of price insted of price_id
            'amount_charge_percent' => $firstPriceMapping['amount_charge_percent'] ?? 2,
        ];

        $subaccountData['priceMappingData'] = $priceMappingData;

        $orderData                   = ['contactId' => $contactId];
        $subaccountData['orderData'] = $orderData;

        // dd($subaccountData);

        $userSetting = UserSetting::where('email', $email)->first();

        // dd($userSetting);

        if ($userSetting) {
            // if(!$userSetting->contact_id){  for just update if not already set these data in UserSetting (subaccount)

            // };

            $updateData = [
                'stripe_payment_method_id' => $subaccountData['stripeData']['stripe_payment_method_id'] ?? null,
                'stripe_customer_id'       => $subaccountData['stripeData']['stripe_customer_id'] ?? null,
                'contact_id'               => $subaccountData['orderData']['contactId'] ?? null,
                'threshold_amount'         => $subaccountData['priceMappingData']['threshold_amount'] ?? null,
                'currency'                 => $subaccountData['priceMappingData']['currency'] ?? null,
                'price_id'                 => $subaccountData['priceMappingData']['price_id'] ?? null,
                'amount_charge_percent'    => $subaccountData['priceMappingData']['amount_charge_percent'] ?? 2,
            ];

            $userSetting->update($updateData);
            Log::info('Updated UserSetting for primary order', ['email' => $email]);

            // Cache::forget($stripeDataKey);
        } else {
            $subaccountDataKey = getSubaccountDataCacheKey($email);
            $ttl               = now()->addMinutes(30);

            Cache::put($subaccountDataKey, $subaccountData, $ttl);

            SubaccountDataBackup::create([
                'email' => $email,
                'data'  => $subaccountData, //json_encode(),
            ]);

            Log::info('Cached and backed up subaccount data for email', ['email' => $email]);

            // Cache::forget($stripeDataKey); // TODO: review it
        }

        Cache::forget($stripeDataKey);
    }
}
