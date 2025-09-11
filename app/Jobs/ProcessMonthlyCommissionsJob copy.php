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

class ProcessMonthlyCommissionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $carbonNow        = Carbon::now();
        $startOfLastMonth = $carbonNow->subMonth()->startOfMonth();
        $endOfLastMonth   = $carbonNow->subMonth()->endOfMonth();
        $today            = $carbonNow->startOfDay();

        $subaccounts = UserSetting::where(['chargeable' => 1, 'paused' => 0])->get();

        foreach ($subaccounts as $subaccount) {
            $lastCheckedAt = $subaccount->last_checked_at ?? $startOfLastMonth;

            $transactionsQuery = Transaction::where('location_id', $subaccount->location_id)
                ->whereBetween('created_at', [$lastCheckedAt, $today]);

            // Calculate amounts
            $totalCommission  = (clone $transactionsQuery)->sum('sum_commission_amount');
            $paidCommission   = (clone $transactionsQuery)->where('status', 1)->sum('sum_commission_amount');
            $unpaidCommission = $totalCommission - $paidCommission;
            $thresholdAmount  = $subaccount->threshold_amount;

            // Calculate invoice amount based on threshold
            $invoiceAmount = $totalCommission > $thresholdAmount
            ? $unpaidCommission
            : ($thresholdAmount - $totalCommission) + $unpaidCommission;

            // dd($invoiceAmount);

            if ($invoiceAmount <= 0) {
                Log::info('No invoice needed', ['location_id' => $subaccount->location_id]);
                continue;
            }

            // Generate invoice in GHL
            $invoiceResponse = $this->generateGhlInvoice($subaccount, $invoiceAmount);
            $invoiceId       = $invoiceResponse['id'] ?? null;

            if ($invoiceId) {
                                                              // Update unpaid transactions with invoice ID and collect updated IDs
                $updatedOrderIds = (clone $transactionsQuery) // TOOD: comment this query
                    ->where('status', '!=', 1)
                    ->pluck('id')
                    ->toArray();

                (clone $transactionsQuery)
                    ->where('status', '!=', 1)
                    ->update(['invoice_id' => $invoiceId]);

                // Pause account after invoice generation
                $subaccount->update(['pause_at' => Carbon::now()->addDays(7)]);

                Log::info('Invoice generated successfully', [
                    'location_id'    => $subaccount->location_id,
                    'contact_id'     => $subaccount->contact_id,
                    'invoice_id'     => $invoiceId,
                    'updated_orders' => $updatedOrderIds, // TODO: no need this so will be commented
                ]);
            } else {
                // If invoice creation failed, pause account anyway
                $subaccount->update(['pause_at' => Carbon::now()->addDays(7)]);

                Log::error('Invoice generation failed', ['location_id' => $subaccount->location_id]);
            }
        }
    }

    private function generateGhlInvoice(UserSetting $subaccount, float $invoiceAmount): array
    {
        $locationId = $subaccount->location_id;
        $userId     = $subaccount->user_id;
        $contactId  = $subaccount->contact_id;
        $currency   = $subaccount->currency ?? 'USD';

        $payload = [
            'altId'          => $locationId,
            'altType'        => 'location',
            'name'           => 'New Invoice',
            'currency'       => $currency,
            'items'          => [[
                'name'        => 'Monthly commission charge',
                'description' => 'Commission charge for previous month',
                'currency'    => $currency,
                'amount'      => $invoiceAmount,
                'qty'         => 1,
            ]],
            'discount'       => ['value' => 0, 'type' => 'percentage'],
            'termsNotes'     => '<p>This is a default terms.</p>',
            'title'          => 'Monthly Commission Invoice',
            'contactDetails' => ['id' => $contactId],
            'issueDate'      => Carbon::now()->toIso8601String(),
            'dueDate'        => Carbon::now()->addDays(7)->toIso8601String(),
        ];

        // Use CRM helper to create invoice
        $response = CRM::crmV2Loc($userId, $locationId, 'invoices/', 'POST', $payload);
        Log::error('GHL invoice creation response: ', ['response' => $response]);

        if (isset($response->id)) {
            return (array) $response;
        }

        Log::error('GHL invoice creation failed', ['response' => $response]);
        return [];
    }
}
