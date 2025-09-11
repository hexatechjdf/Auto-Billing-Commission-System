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

class ProcessMonthlyInvoicesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $startDateofmonth = Carbon::now()->subMonth()->startOfMonth();
        $endDateOfMonth   = Carbon::now()->subMonth()->endOfMonth();

        $subaccounts = UserSetting::all();

        foreach ($subaccounts as $userSetting) {
            $locationId  = $userSetting->location_id;
            $threshold   = $userSetting->threshold_amount;
            $lastChecked = $userSetting->last_checked_at;

            $periodStart = $lastChecked ? $lastChecked->addDay()->startOfDay() : $startDateofmonth;
            $periodEnd   = Carbon::now()->subDay()->endOfDay();

            $transactions = Transaction::where('location_id', $locationId)
                ->whereBetween('created_at', [$periodStart, $periodEnd]);

            $totalCommissionAmount  = $transactions->sum('sum_commission_amount');
            $paidCommissionAmount   = $transactions->where('status', 1)->sum('sum_commission_amount');
            $unpaidCommissionAmount = $totalCommissionAmount - $paidCommissionAmount;

            if ($totalCommissionAmount > $threshold) {
                $amountToCharge = $unpaidCommissionAmount;
            } else {
                $thresholdShortfall = $threshold - $totalCommissionAmount;
                $amountToCharge     = $thresholdShortfall + $unpaidCommissionAmount;
            }

            $userSetting->update(['last_checked_at' => Carbon::now()]);

            // Generate GHL invoice
            $invoiceId = $this->createGhlInvoice($locationId, $userSetting->contact_id, $amountToCharge, $userSetting->currency);

            if ($invoiceId) {
                $transactions->where('status', '!=', 1)->update(['invoice_id' => $invoiceId]);
                $userSetting->update(['pause_at' => Carbon::now()->addDays(7)]);
            } else {
                Log::error('Failed to generate GHL invoice', ['locationId' => $locationId]);
            }
        }

        Log::info('Monthly invoices processed successfully');
    }

    protected function createGhlInvoice($locationId, $contactId, $amountToCharge, $currency)
    {
        // Use CRM to make API call to create invoice
        $payload = [
            "invoiceName" => "Monthly Commission Invoice",
            "contactId"   => $contactId,
            "lineItems"   => [
                [
                    "name"        => "Commission Charge",
                    "description" => "Monthly commission charge",
                    "amount"      => $amountToCharge * 100, // Assuming in cents or smallest unit
                    "quantity"    => 1,
                    "currency"    => $currency,
                ],
            ],
            // Add other required fields based on GHL API
        ];

        $response = CRM::crmV2Loc( /* company_id */, $locationId, 'invoices/', 'POST', $payload);

        if ($response && property_exists($response, 'invoiceId')) {
            return $response->invoiceId; // Adjust based on actual response field
        }

        return null;
    }

    // private function generateGhlInvoice(UserSetting $subaccount, $invoiceAmount)
    // {
    //     $locationId = $subaccount->location_id;
    //     $userId     = $subaccount->user_id;
    //     $contactId  = $subaccount->contact_id;
    //     $currency   = $subaccount->currency ?? 'USD';

    //     $payload = [
    //         'altId'          => $locationId,
    //         'altType'        => 'location',
    //         // "businessDetails" => [
    //         //     "name"    => "Alex",
    //         //     "address" => [
    //         //         "addressLine1" => "9931 Beechwood",
    //         //         "city"         => "St. Houston",
    //         //         "state"        => "TX",
    //         //         "countryCode"  => "USA",
    //         //         "postalCode"   => "559-6993",
    //         //     ],
    //         //     "phoneNo" => "+1-214-559-6993",
    //         //     "website" => "www.example.com",
    //         // ],
    //         "name"           => "New Invoice",
    //         "currency"       => $currency,
    //         'items'          => [
    //             [
    //                 "name"        => "Monthly commission charge",
    //                 'description' => 'Unachieved Minimum Target: //TODO',
    //                 "currency"    => $currency,
    //                 'amount'      => $invoiceAmount,
    //                 'qty'         => 1,
    //             ],
    //         ],
    //         "discount"       => [
    //             "value" => 0,
    //             "type"  => "percentage",
    //         ],
    //         "termsNotes"     => "<p>This is a default terms.</p>",
    //         "title"          => " Test INVOICE",

    //         "contactDetails" => [
    //             "id" => $contactId,
    //             // "name"    => "Alex",
    //             // "phoneNo" => "+1234567890",
    //             // "email"   => "alex@example.com",
    //         ],

    //         'issueDate'      => Carbon::now()->toIso8601String(),
    //         'dueDate'        => Carbon::now()->addDays(7)->toIso8601String(),
    //         // 'notes'          => 'Commission charge for the month: //TODO add month ',
    //         // "sentTo"         => [
    //         //     "email" => [
    //         //         "alex@example.com",
    //         //     ],
    //         // ],
    //         // "liveMode" =>true,

    //     ];

    //     // Use CRM helper to create invoice
    //     $response = CRM::crmV2Loc($userId, $locationId, 'invoices/', 'POST', $payload);
    //     Log::error('GHL invoice creation response.', ['response' => $response]);

    //     if (isset($response->id)) {
    //         return (array) $response;
    //     } else {
    //         Log::error('GHL invoice creation failed', ['response' => $response]);
    //         return [];
    //     }
    // }
}
