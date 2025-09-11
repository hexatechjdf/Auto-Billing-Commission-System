<?php
namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
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
        // $liveMode   = $this->payload['liveMode'] ?? false;
        $metadata   = $this->payload;

        if ($status !== 'completed' || ! $orderId || ! $locationId) {
            Log::error('Invalid GHL OrderStatusUpdate webhook payload', ['payload' => $this->payload]);
            return;
        }

        // Save or update the order
        $order = Order::updateOrCreate(
            ['order_id' => $orderId],
            [
                'contact_id'  => $contactId,
                // 'live_mode'   => $liveMode,
                'location_id' => $locationId,
                'user_id'     => auth()->id() ?? 'system', // Use authenticated user or 'system' for webhooks
                'amount'      => $amount,
                'currency'    => $currency,
                //'status'      => $status,
                'metadata'    => json_encode($metadata),
            ]
        );

        // Save order items
        $items = $this->payload['items'] ?? [];
        foreach ($items as $item) {
            OrderItem::updateOrCreate(
                ['order_id' => $order->id, 'price_id' => $item['price']['_id']],
                [
                    'item_name'    => $item['name'],
                    'qty'          => $item['qty'],
                    'product_id'   => $item['product']['_id'],
                    'product_name' => $item['product']['name'],
                    'price_id'     => $item['price']['_id'],
                    'price_name'   => $item['price']['name'],
                    'amount'       => $item['price']['amount'],
                    'currency'     => $item['price']['currency'],
                    'type'         => $item['price']['type'],
                    'metadata'     => json_encode($item),
                ]
            );
        }

        Log::info('Processed GHL OrderStatusUpdate webhook', ['orderId' => $orderId, 'locationId' => $locationId]);
    }
}
