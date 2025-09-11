<?php
namespace App\Jobs;

use App\Helper\CRM;
use App\Models\Transaction;
use App\Models\UserSetting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stripe\StripeClient;

class ProcessMonthlyCommissionsChargeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }
    public function handle()
    {
        $carbonNow        = $this->payload['carbonNow'];
        $startOfLastMonth = $this->payload['startOfLastMonth'];
        $endOfLastMonth   = $this->payload['endOfLastMonth'];
        $today            = $this->payload['today']; // todayStart
        $pauseAtDate      = $carbonNow->addDays(7);

        // $today = '2025-09-11 00:00:00'; // TODO: remove it

        //foreach ($subaccounts as $subaccount) {
        // if ($subaccount->location_id != 'iH97EKkQZYraKdjvKRN0') {
        //     continue;
        // }
        $subaccount    = $this->payload['subaccount'];
        $lastCheckedAt = $subaccount->last_checked_at ?? $startOfLastMonth;

        // dd($startOfLastMonth, $endOfLastMonth, $lastCheckedAt, $today);

        $locationId = $subaccount->location_id;

        $transactionsQuery = Transaction::where('location_id', $locationId)
            ->whereBetween('created_at', [$lastCheckedAt, $today]); // TODO: confirm $today or $endOfLastMonth, also when updating last_checked_at

        // Calculate amounts
        $totalCommission = $transactionsQuery->clone()->sum('sum_commission_amount');

        $paidCommission   = $transactionsQuery->clone()->where('status', 1)->sum('sum_commission_amount');
        $unpaidCommission = $totalCommission - $paidCommission;
        $thresholdAmount  = $subaccount->threshold_amount;

        // Calculate threshold shortfall if any
        $thresholdShortfall = $totalCommission < $thresholdAmount
            ? $thresholdAmount - $totalCommission
            : 0;

        // dd($thresholdAmount, $totalCommission, $thresholdShortfall);

        // Final invoice amount
        $invoiceAmount = $unpaidCommission + $thresholdShortfall;

        if ($invoiceAmount <= 0) {
            Log::info('No invoice needed', ['location_id' => $locationId]);
            return;
        }

        // dd($totalCommission, $paidCommission, $unpaidCommission, $thresholdAmount, $invoiceAmount);

        // Fetch unpaid transactions
        $unpaidTransactions = $transactionsQuery->clone()
            ->whereNot('status', Transaction::STATUS_PAID)
            ->select(['id', 'location_id', 'sum_commission_amount', 'created_at'])
            ->get();

        // Prepare invoice items
        $invoiceItems = $this->prepareInvoiceItems(
            $unpaidTransactions,
            $thresholdShortfall,
            $subaccount,
            $startOfLastMonth
        );

        $unpaidTransactionIds = $unpaidTransactions->pluck('id')->implode(',');

        // dd($invoiceItems);

        // 1: Try Stripe payment first
        $stripePaymentSucceeded = false;
        $stripePaymentId        = null;
        $stripeSecretKey        = supersetting($key = 'stripe_secret_key');

        $stripeCustomerId      = $subaccount->stripe_customer_id;
        $stripePaymentMethodId = $subaccount->stripe_payment_method_id;
        $currency              = $subaccount->currency;

        if (isset($stripeSecretKey) && $stripeCustomerId && $stripePaymentMethodId) {
            try {

                // Stripe::setApiKey($stripeSecretKey);
                $stripe = new StripeClient($stripeSecretKey);

                $stripeIntentPayload = [
                    'amount'         => intval($invoiceAmount * 100),
                    'currency'       => $currency,
                    'customer'       => $stripeCustomerId,
                    'payment_method' => $stripePaymentMethodId,
                    'off_session'    => true,
                    'confirm'        => true,
                    'description'    => 'Monthly Commission charge for location ' . $locationId,
                    'metadata'       => [
                        'location_id'            => $locationId,
                        'unpaid_transaction_ids' => $unpaidTransactionIds,
                        'threshold_shortfall'    => $thresholdShortfall,
                        'invoiceItems'           => $invoiceItems,
                    ],
                ];

                // dd($stripeIntentPayload);
                // Create a payment intent
                $paymentIntent = $stripe->paymentIntents->create($stripeIntentPayload);

                if ($paymentIntent->status === 'succeeded') {
                    $stripePaymentSucceeded = true;
                    $stripePaymentId        = $paymentIntent->id;
                }
            } catch (\Exception $e) {
                Log::error('Stripe payment failed', [
                    'location_id' => $locationId,
                    'error'       => $e->getMessage(),
                ]);
            }
        }

        if ($thresholdShortfall > 0) {
            //insert record to transactions table either with status $stripePaymentSucceeded

            //TODO: confirm this then uncomment
            // $thresholdTransaction = Transaction::create([    // maybe I think add a column is_threshold = 1 or add created_at with lastMonth date so that its not include in next month calculations
            //     'location_id'           => $locationId,
            //     'sum_commission_amount' => $thresholdShortfall, // TODO: maybe need in a separate column for thresholdShortfall amount
            //     'currency'              => $currency,
            //     'status'                => 0, // pending
            //     'metadata'              => ['type' => 'threshold Shortfall Transaction', 'stripe_paymentIntentId' => $stripePaymentId],
            //     'charged_at'            => null,
            //     'reason'                => 'Threshold Shortfall Transaction',
            //     // 'pm_intent' => $stripePaymentId,
            //     'invoice_id'            => null,
            // ]);
        }

        // 2: If Stripe succeeded, update transactions & subaccount
        if ($stripePaymentSucceeded && $stripePaymentId) {
            $transactionsQuery->clone()
                ->whereNot('status', Transaction::STATUS_PAID)
                ->update([
                    'status'   => 1,
                    'metadata' => json_encode(['stripe_paymentIntentId' => $stripePaymentId]),
                ]);

            $subaccount->update([
                //'pause_at'        => $pauseAtDate,
                'last_checked_at' => $today,
            ]);

            Log::info('Stripe payment successful', [
                'location_id'            => $subaccount->location_id,
                'stripe_paymentIntentId' => $stripePaymentId,
                'amount'                 => $invoiceAmount,
                'invoiceItems'           => $invoiceItems,

            ]);

            return; // Skip GHL invoice since Stripe succeeded
        }

        //3: If Stripe failed, fallback to GHL invoice
        $invoiceResponse = $this->generateGhlInvoice($subaccount, $invoiceItems, $startOfLastMonth, $pauseAtDate, $carbonNow);
        $invoiceId       = $invoiceResponse['_id'] ?? null;

        if ($invoiceId) {
            $transactionsQuery->clone()
                ->whereNot('status', Transaction::STATUS_PAID)
                ->update(['invoice_id' => $invoiceId]);

            // $thresholdTransaction->update(['invoice_id' => $invoiceId]);

            $subaccount->update([
                'pause_at'        => $pauseAtDate,
                'last_checked_at' => $today,
            ]);

            Log::info('Invoice generated via GHL', [
                'location_id'           => $subaccount->location_id,
                'invoice_id'            => $invoiceId,
                'total_amount'          => $invoiceAmount,
                'unpaid_transactionIds' => $unpaidTransactionIds,
            ]);
        } else {
            $subaccount->update([
                'pause_at'        => $pauseAtDate,
                'last_checked_at' => $today,
            ]);

            Log::error('GHL invoice generation failed', [
                'location_id' => $subaccount->location_id,
            ]);
        }
    }

    //TODO: Move these below methods into service pattren

    /**
     * Prepare invoice items array
     */
    private function prepareInvoiceItems($unpaidTransactions, float $thresholdShortfall, UserSetting $subaccount, Carbon $startOfLastMonth): array
    {
        $items = [];

        // Add threshold shortfall as a separate item if applicable
        if ($thresholdShortfall > 0) {
            $items[] = [
                'name' => "Threshold Shortfall #{$startOfLastMonth->format('F Y')}",
                'description' => "Unachieved minimum commission target for {$startOfLastMonth->format('F Y')}",
                'currency' => $subaccount->currency,
                'amount'   => $thresholdShortfall,
                'qty'      => 1,
            ];
        }

        // Add each unpaid transaction as a separate invoice item
        foreach ($unpaidTransactions as $transaction) {
            $items[] = [
                'name' => "Commission for Unpaid Transaction #{$transaction->id}",
                'description' => "Commission from Unpaid Transaction {$transaction->created_at->format('d M Y')} (loc_{$transaction->location_id})",
                'currency' => $subaccount->currency,
                'amount' => $transaction->sum_commission_amount,
                'qty' => 1,
            ];
        }

        return $items;
    }

    /**
     * Generate GHL invoice with multiple items
     */
    private function generateGhlInvoice(UserSetting $subaccount, array $items, Carbon $startOfLastMonth, Carbon $pauseAtDate, Carbon $carbonNow): array
    {
        $locationId = $subaccount->location_id;
        $userId     = $subaccount->user_id;
        $contactId  = $subaccount->contact_id;

        $contactName  = $subaccount->contact_name ?? "Zeeshan jdfunnel"; // TODO: remove hardcoded name, phone, businessName
        $contactPhone = $subaccount->contact_phone ?? "+923146363255";
        $businessName = supersetting($key = 'crm_business_name') ?? "Mohsin Tech";

        $currency        = $subaccount->currency;
        $subaccountEmail = $subaccount->email;

        $payload = [
            'altId'   => $locationId,
            'altType' => 'location',
            'name'    => "Monthly Commission Invoice - {$startOfLastMonth->format('F Y')}",
            'currency'        => $currency,
            "businessDetails" => [
                "name" => $businessName,
                // "address" => [
                //     "addressLine1" => "Central Park Housing Society, Lahore",
                //     "city"         => "Lahore",
                //     "state"        => "Punjab",
                //     "countryCode"  => "PK",
                //     "postalCode"   => "5000",
                // ],
                // "phoneNo" => "+1-214-559-6993",
                // "website" => "www.example.com",
            ],
            'items'           => $items,
            // 'discount'        => ['value' => 0, 'type' => 'percentage'],
            'termsNotes'      => '<p>This is a default terms.</p>',
            'title'           => 'Monthly Transactions Commission Invoice',
            // TODO: Call contactApi to get contact details Or retrive and save the contact details in user_settig on subaccount creation
            'contactDetails'  => [
                "id"      => (string) $contactId,
                "name"    => $contactName,
                "phoneNo" => $contactPhone,
                // "email"   => "zeeshanahmadjdfunnel@gmail.com",
            ],
            'issueDate'       => $carbonNow->format('Y-m-d'),
            'dueDate'         => $pauseAtDate->format('Y-m-d'),
            "sentTo"          => [
                "email" => [
                    $subaccountEmail,
                ],
            ],
        ];

        // dd($payload);

        $response = CRM::crmV2Loc($userId, $locationId, 'invoices/', 'POST', $payload);

        Log::info('GHL invoice creation response', ['response' => $response]);

        if (isset($response->{'_id'})) {
            return (array) $response;
        }

        Log::error('GHL invoice creation failed', ['response' => $response]);
        return [];
    }
}
