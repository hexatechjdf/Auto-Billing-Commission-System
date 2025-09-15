<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\UserSetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessGhlOrderStatusUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;

    public function __construct(array $payload)
    {
        $this->payload = $payload;
    }

    public function handle()
    {
        $status     = $this->payload['status'] ?? null;
        $orderId    = $this->payload['_id'] ?? null;
        $locationId = $this->payload['locationId'] ?? null;
        $contactId  = $this->payload['contactId'] ?? null;
        $currency   = $this->payload['currency'] ?? 'USD';
        $amount     = $this->payload['amount'] ?? 0;
        // $metadata   = $this->payload;

        if ($status !== 'completed' || ! $orderId || ! $locationId) {
            Log::error('Invalid GHL OrderStatusUpdate webhook payload', ['payload' => $this->payload]);
            return;
        }

        $userSetting = UserSetting::where('location_id', $locationId)->first();

        if (! $userSetting) {
            Log::error('UserSetting not found for locationId', ['locationId' => $locationId]);
            return; // TODO: maybe don't return if record not exist (review it)
        }

        $amountChargePercent        = $userSetting->amount_charge_percent;
        $calculatedCommissionAmount = $amount * ($amountChargePercent / 100);

        Order::updateOrCreate( //TODO: mayby no update
            ['order_id' => $orderId],
            [
                'contact_id'                   => $contactId,
                'location_id'                  => $locationId,
                'user_id'                      => auth()->id() ?? 'system', // TODO get the user by location id  etc if need. (right now no need)
                'amount'                       => $amount,
                'currency'                     => $currency,
                'amount_charge_percent'        => $amountChargePercent,
                'calculated_commission_amount' => $calculatedCommissionAmount,
                'transaction_id'               => null,
                'status'                       => $status,
                'payload'                      => $this->payload,
            ]
        );

        Log::info('Processed GHL OrderStatusUpdate webhook', ['orderId' => $orderId, 'locationId' => $locationId]);
    }
}
