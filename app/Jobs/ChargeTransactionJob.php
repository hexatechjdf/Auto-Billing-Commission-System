<?php
namespace App\Jobs;

use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ChargeTransactionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public $transaction) // before int $transactionId
    {}

    public function handle(): void
    {
        $transaction   = $this->transaction;
        $transactionId = $transaction->id;

        Log::info('ChargeTransactionJob hit', ['transactionId' => $transactionId]);

        // if (! $transaction) {
        //     Log::error('Transaction not found', ['transactionId' => $this->transactionId]);
        //     return;
        // }

        // Get Stripe credentials for this location
        $userSetting = UserSetting::where('location_id', $transaction->location_id)->first();

        if (! $userSetting || ! $userSetting->stripe_customer_id || ! $userSetting->stripe_payment_method_id) {
            $transaction->update([
                'status' => 2, // failed
                'reason' => 'Missing Stripe customer or payment method ID',
            ]);
            Log::error('Missing Stripe customer or payment method ID for transaction', ['transactionId' => $transactionId, 'locatoinId' => $transaction->location_id]);
            return;
        }

        $stripeSecretKey = supersetting($key = 'stripe_secret_key');

        if (! $stripeSecretKey) {
            $transaction->update([
                'status' => 2, // failed
                'reason' => 'Missing Stripe secret_key',
            ]);
            Log::error('Missing stripe_secret_key', ['transactionId' => $this->transactionId]);
            return;
        }

        $stripe = new StripeClient($stripeSecretKey);

        try {
            // Create a payment intent
            $paymentIntent = $stripe->paymentIntents->create([
                'amount'         => (int) ($transaction->sum_commission_amount * 100),
                'currency'       => $transaction->currency,
                'customer'       => $userSetting->stripe_customer_id,
                'payment_method' => $userSetting->stripe_payment_method_id,
                'off_session'    => true,
                'confirm'        => true,
                'description'    => 'Commission charge for location ' . $transaction->location_id,
                'metadata'       => [
                    'bc_transaction_id' => $transaction->id,
                    'bc_location_id'    => $transaction->location_id,
                ],
            ]);

            if ($paymentIntent->status === 'succeeded') {
                $transaction->update([
                    'status'     => 1,
                    'charged_at' => Carbon::now(), // TODO check timezone
                    'pm_intent'  => $paymentIntent->id,
                ]);
            } else {
                $transaction->update([
                    'status'    => 2,
                    'reason'    => 'Stripe status: ' . $paymentIntent->status,
                    'pm_intent' => $paymentIntent->id,
                ]);
            }
            Log::info('Transaction charged successfully', ['transactionId' => $transactionId]);

        } catch (\Exception $e) {
            $transaction->update([
                'status' => 2,
                'reason' => $e->getMessage(),
            ]);
            Log::error('Failed to charge transaction', ['transactionId' => $transactionId, 'error' => $e->getMessage()]);
        }
    }
}
