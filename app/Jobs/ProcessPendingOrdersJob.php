<?php
namespace App\Jobs;

use App\Jobs\ChargeTransactionJob;
use App\Models\Order;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPendingOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries   = 3;                          // Retry up to 3 times on failure
    public $backoff = [60 * 5, 60 * 10, 60 * 15]; //[60, 120, 300]; // Wait 5min, 10min, 15min between retries

    public function handle(): void
    {

        // ChargeTransactionJob::dispatchSync(2); //TODO:  dispatch
        // Log::info("ChargeTransactionJob dispatched for transaction ID: {2}");
        // dd('ChargeTransactionJob dispatchedfor');

        // Calculate date range: last 5 full days, excluding today
        $startDate = Carbon::now()->subDays(5)->startOfDay();
        $endDate   = Carbon::yesterday()->endOfDay();

        // Base query for unprocessed orders
        $ordersQuery = Order::whereNull('transaction_id')
            ->whereBetween('created_at', [$startDate, $endDate]);

        // Fetch user settings for all relevant locations
        // $locationIds  = $ordersQuery->clone()->groupBy('location_id')->pluck('location_id');
        // $userSettings = UserSetting::whereIn('location_id', $locationIds)
        //     ->pluck('currency', 'location_id')
        //     ->toArray();

        // dd($startDate, $endDate, $locationIds, $userSettings);

        // Group by location and calculate sum of commissions
        $orderLocations = $ordersQuery->clone()
            ->groupBy('location_id')
            ->selectRaw('location_id, SUM(calculated_commission_amount) as sum_commission_amount')
            ->get(); //->cursor();
        $currency = 'USD';
        //$userSettings[$locationId] ?? 'USD';

        // dd($orderLocations);

        foreach ($orderLocations as $orderLocation) {
            $locationId = $orderLocation->location_id;

            // if (! isset($userSettings[$locationId])) {
            //     Log::error('UserSetting not found for locationId', ['locationId' => $locationId]);
            //     continue;
            // }

            DB::transaction(function () use ($ordersQuery, $locationId, $orderLocation, $currency) { // , $userSettings
                try {
                    $sumCommissionAmount = $orderLocation->sum_commission_amount;

                    // $orderIds = $ordersQuery->clone()
                    //     ->where('location_id', $locationId)
                    //     ->pluck('id')
                    //     ->toArray();

                    $transaction = Transaction::create([
                        'location_id'           => $locationId,
                        'sum_commission_amount' => $sumCommissionAmount,
                        'currency'              => $currency,
                        'status'                => 0,    // pending
                        'metadata'              => null, // TODO: 'metadata' => ['order_ids' => $orderIds]
                        'charged_at'            => null,
                        'reason'                => null,
                        'invoice_id'            => null,
                    ]);

                    $updated = $ordersQuery->clone()
                        ->where('location_id', $locationId)
                        ->update(['transaction_id' => $transaction->id]);

                    Log::info("Updated {$updated} orders for transaction ID: {$transaction->id}");

                    ChargeTransactionJob::dispatch($transaction); // Tested with sync
                    Log::info("ChargeTransactionJob dispatched for transaction ID: {$transaction->id}");

                } catch (\Exception $e) {
                    Log::error("Failed to process orders for location ID: {$locationId}", [
                        'error' => $e->getMessage(),
                    ]);
                    // throw $e; // Trigger retry if job fails
                }
            });
        }
    }
}
