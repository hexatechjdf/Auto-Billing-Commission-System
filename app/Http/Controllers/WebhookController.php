<?php
namespace App\Http\Controllers;

use App\Services\HandlerService;
use Illuminate\Http\Request;

class WebhookController extends Controller
{

    public function __construct(
        protected HandlerService $handlerService
    ) {}

    public function handleGhlWebhook(Request $request)
    {
        $payload = $request->all();
        $log     = ['endpoint' => 'webhooks.crm', 'payload' => $payload];

        $webhookType = $payload['type'] ?? null;

        return match ($webhookType) {
            'INSTALL'           => $this->handlerService->handleGhlLocationConnectWebhook($payload, $log),
            'UNINSTALL'         => $this->handlerService->handleGhlAppUninsalllWebhook($payload, $log),
            'LocationCreate', 'LocationUpdate' => $this->handlerService->handleGhlLocationWebhook($webhookType, $payload, $log),
            // 'PriceCreate'       => $this->handlerService->handlePriceCreate($webhookType, $payload, $log), //TODO: test it
            'OrderStatusUpdate' => $this->handlerService->handleGhlOrderStatusUpdate($webhookType, $payload, $log),
            'InvoicePaid', 'invoice.paid'      => $this->handlerService->handleGhlInvoicePaid($webhookType, $payload, $log),
            default             => response()->json(['message' => 'Unhandled webhook type'], 200),
        };
    }

    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->all();
        $log     = ['endpoint' => 'webhooks.stripe', 'payload' => $payload];

        $webhookType = $payload['type'] ?? null;

        return match ($webhookType) {
            'payment_method.attached', 'payment_method.detached' => $this->handlerService->handleStripePaymentMethodWebhook($webhookType, $payload, $log),
            'payment_intent.succeeded', 'payment_intent.payment_failed', => $this->handlerService->handleStripePaymentIntentWebhook($webhookType, $payload, $log),
            default => response()->json(['message' => 'Unhandled webhook type'], 200),
        };
    }

}
