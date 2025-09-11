<?php
namespace App\Http\Controllers;

use App\Helper\CRM;

//  use App\Jobs\{ProcessGhlLocationWebhook, ProcessGhlOrderStatusUpdate, ProcessPrimaryOrderJob, ProcessStripeWebhook};

use App\Jobs\ConnectSubaccountJob;
use App\Jobs\ProcessGhlLocationWebhook;
use App\Jobs\ProcessGhlOrderStatusUpdate;
use App\Jobs\ProcessPrimaryOrderJob;
use App\Models\UserSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleGhlWebhook(Request $request)
    {
        $payload = $request->all();
        $log     = ['endpoint' => 'webhooks.crm', 'payload' => $payload];

        $webhookType = $payload['type'] ?? null;

        return match ($webhookType) {
            'INSTALL'           => $this->handleGhlLocationConnectWebhook($payload, $log),
            'UNINSTALL'         => $this->handleGhlAppUninsalllWebhook($payload, $log),
            'LocationCreate', 'LocationUpdate' => $this->handleGhlLocationWebhook($webhookType, $payload, $log),
            // 'PriceCreate'       => $this->handleGhlPriceCreate($webhookType, $payload, $log), //TODO: test it
            'OrderStatusUpdate' => $this->handleGhlOrderStatusUpdate($webhookType, $payload, $log),
            'InvoicePaid', 'invoice.paid'      => $this->handleGhlInvoicePaid($webhookType, $payload, $log),
            default             => response()->json(['message' => 'Unhandled webhook type'], 200),
        };
    }

    protected function handleGhlAppUninsalllWebhook(array $payload, array $log)
    {
        $locationId = $payload['locationId'] ?? null;

        if (! $locationId) {
            Log::error('Missing location ID in GHL webhook', $log);
            return response()->json(['success' => false, 'message' => 'Missing location ID'], 200);
        }

        //TOOD: add the appId Check before processing to make sure webhhok is based on our Marketplace App.

        $appId = $payload['appId'] ?? null;

        // if ($appId != 'BillingSystem appId') {
        //     Log::error('Missing location ID in GHL webhook', $log);
        //     return response()->json(['success' => false, 'message' => 'appId Other then Auto-Billing-System, Skiped'], 200);
        // }

        $subaccount = UserSetting::where('location_id', $locationId)
            ->select('allow_uninstall', 'user_id')
            ->first();

        if (! $subaccount) {
            return response()->json(['success' => false, 'message' => 'No subaccount not found'], 200);
        }

        $companyId = CRM::getCrmToken(['location_id' => $locationId])?->company_id;

        if (! $companyId) {
            return response()->json(['success' => false, 'message' => 'No companyId not found'], 200);
        }

        if (! $subaccount->allow_uninstall) {
            $dbUserId = $subaccount->user_id;
            ConnectSubaccountJob::dispatch($companyId, $locationId, $dbUserId);
        }

        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    protected function handleGhlLocationConnectWebhook(array $payload, array $log)
    {
        $locationId  = $payload['locationId'] ?? null;
        $installType = $payload['installType'] ?? null;

        if (! $locationId) {
            Log::error('Missing location ID in GHL webhook', $log);
            return response()->json(['success' => false, 'message' => 'Missing location ID'], 200); //TODO confirm the status code to return (I updated 400 -> 200)
        }

        if (! $installType != 'Location') {
            Log::error('InstallType is not  location in webhook, skiped!', $log);
            return response()->json(['success' => false, 'message' => 'installType is not location'], 200); //TODO confirm the status code to return (I updated 400 -> 200)
        }

        ProcessGhlLocationWebhook::dispatchSync($payload); // ->delay(now()->addMinutes(3)); //TODO: dispatch
        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    protected function handleGhlLocationWebhook(string $webhookType, array $payload, array $log)
    {
        $locationId = $payload['id'] ?? null;
        if (! $locationId) {
            Log::error('Missing location ID in GHL webhook', $log);
            return response()->json(['success' => false, 'message' => 'Missing location ID'], 200); //TODO confirm the status code to return (I updated 400 -> 200)
        }

        ProcessGhlLocationWebhook::dispatchSync($payload); // ->delay(now()->addMinutes(3)); //TODO: dispatch
        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    public function handlePriceCreate(Request $request) // TODO : test it
    {
        $payload = $request->all();

        $locationId = $payload['locationId'] ?? null;
        $priceId    = $payload['_id'] ?? null;

        if (! $locationId || ! $priceId) {
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 200); //TODO confirm the status code to return
        }

        $response = CRM::fetchInventories($locationId);

        if (! $response['status']) {
            return response()->json(['success' => false, 'message' => $response['message']], 400);
        }

        $inventories = $response['inventories'];
        $newPrice    = collect($inventories)->firstWhere('_id', $priceId);

        if (! $newPrice) {
            return response()->json(['success' => false, 'message' => 'New price not found'], 404);
        }

        $planMappingService = app(PlanMappingService::class);
        $planMappingService->syncPlanMappingsForLocation($locationId, [$newPrice]);

        return response()->json(['success' => true, 'message' => 'New price synced successfully']);
    }

    protected function handleGhlOrderStatusUpdate(string $webhookType, array $payload, array $log)
    { // TODO: optimize
        $status   = $payload['status'] ?? null;
        $liveMode = $payload['liveMode'] ?? false;

        if ($status !== 'completed') {
            Log::info('Order not completed, skipping processing', $log);
            return response()->json(['success' => true, 'message' => 'Order not completed, skipped'], 200);
        }

        if ($liveMode !== true) {
            Log::info('Order not in live mode, skipping processing', $log);
            return response()->json(['success' => true, 'message' => 'Order not in live mode, skipped'], 200);
        }

        $locationId   = $payload['locationId'] ?? null;
        $orderId      = $payload['_id'] ?? null;
        $contactId    = $payload['contactId'] ?? null;
        $contactEmail = data_get($payload, 'contactSnapshot.email');

        if (! $contactEmail || ! $orderId || ! $locationId || ! $contactId) {
            Log::error('Invalid payload in GHL OrderStatusUpdate webhook', $log);
            return response()->json(['success' => false, 'message' => 'Invalid payload'], 200); // status before 400
        }

        $primarySubaccount = supersetting($key = 'primary_subaccount'); //TODO:

        if (! $primarySubaccount) {
            Log::info('primarySubaccount not set, skipping processing', $log);
            return response()->json(['success' => true, 'message' => 'primarySubaccount not set, skipped'], 200);
        }

        // dd($locationId, $primarySubaccount, $locationId == $primarySubaccount);

        if ($locationId == $primarySubaccount) {

            ProcessPrimaryOrderJob::dispatchSync($payload); //->delay(now()->addMinute(2)); // // TODO: dispatch
        } else {
            ProcessGhlOrderStatusUpdate::dispatchSync($payload); // TODO: dispatch
        }

        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    protected function handleGhlInvoicePaid(string $webhookType, array $payload, array $log)
    {
        $metadata = $payload['metadata'] ?? [];
                                                                               //TODO
        $invoiceId = $metadata['invoice_id'] ?? $payload['invoiceId'] ?? null; // Adjust based on actual payload field

        if (! $invoiceId) {
            Log::error('Missing invoice_id in GHL InvoicePaid webhook payload', $log);
            return response()->json(['success' => false, 'message' => 'Missing invoice_id'], 400);
        }

        $transactionsQuery = Transaction::where('invoice_id', $invoiceId);

        $transaction = $transactionsQuery->first();

        if (! $transaction) {
            Log::warning('No transactions found for invoice_id', ['invoice_id' => $invoiceId]);
            return response()->json(['success' => false, 'message' => 'No transactions found'], 200); // 404
        }

        // Update transactions to paid
        $updatedRows = $transactionsQuery->update(['status' => 1]);

        if ($updatedRows) {
            $userSetting = $transaction->userSetting();

            if ($userSetting) {
                //TODO: not sure but I think if $userSetting->paused  then (when invoice paid ) we also need to call GHL api to locationActive
                $userSetting->update(['pause_at' => null, 'paused' => 0]);
                Log::info('Updated subaccount on CRM invoice paid', ['invoice_id' => $invoiceId]);
            } else {
                Log::error('UserSetting not found on CRM invoice paid', ['invoice_id' => $invoiceId]);
            }
        }

        Log::info("Processed GHL {$webhookType} webhook", ['invoiceId' => $invoiceId]);

        return response()->json(['success' => true, 'message' => 'Webhook processed successfully']);
    }

    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->all();
        $log     = ['endpoint' => 'webhooks.stripe', 'payload' => $payload];

        $webhookType = $payload['type'] ?? null;

        return match ($webhookType) {
            'payment_method.attached', 'payment_method.detached' => $this->handleStripePaymentMethodWebhook($webhookType, $payload, $log),
            'payment_intent.succeeded', 'payment_intent.payment_failed', => $this->handleStripePaymentIntentWebhook($webhookType, $payload, $log),
            default => response()->json(['message' => 'Unhandled webhook type'], 200),
        };

    }

    protected function handleStripePaymentMethodWebhook(string $webhookType, array $payload, array $log)
    {
        ProcessStripePaymentMethodWebhook::dispatchSync($payload); //TODO: dispatch
        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    protected function handleStripePaymentIntentWebhook(string $webhookType, array $payload, array $log)
    {
        // $metadata        = $payload['data']['object']['metadata'] ?? [];
        // $bcTransactionId = $metadata['bc_transaction_id'] ?? null;

        // if ($bcTransactionId) {
        //     return $this->handleTransactionPayments($bcTransactionId, $webhookType, $payload, $log); // TODO: Test it
        // }

        $contactId = $metadata['contactId'] ?? null; //TODO: modify based on available metadata

        if (! $contactId) {
            Log::error('Missing contactId in Stripe webhook metadata', $log);
            return response()->json(['success' => false, 'message' => 'Missing contactId in metadata'], 400);
        }

        ProcessStripeIntentWebhook::dispatchSync($payload); //TODO: dispatch
        return response()->json(['success' => true, 'message' => 'Webhook queued for processing']);
    }

    public function handleTransactionPayments($bcTransactionId, string $webhookType, array $payload, array $log)
    {
        try {
            switch ($webhookType) {
                case 'payment_intent.succeeded':
                    Transaction::where('id', $bcTransactionId)->update([
                        'status'     => 1,
                        'charged_at' => now(),
                    ]);

                    Log::info("Transaction #{$bcTransactionId} marked as succeeded.", $log);
                    break;

                case 'payment_intent.payment_failed':
                    $error = $payload['data']['object']['last_payment_error']['message'] ?? 'Payment failed';
                    Transaction::where('id', $bcTransactionId)->update([
                        'status' => 2,
                        'reason' => $error,
                    ]);

                    Log::error("Transaction #{$bcTransactionId} payment failed: {$error}", $log);
                    break;

                default:
                    Log::notice("Unhandled Stripe webhook type: {$webhookType}", $log);
                    return response()->json([
                        'success' => false,
                        'message' => 'Unhandled webhook type',
                    ], 200);
            }

            return response()->json([
                'status'  => 'ok',
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing Stripe webhook for transaction #{$bcTransactionId}: {$e->getMessage()}", $log);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
