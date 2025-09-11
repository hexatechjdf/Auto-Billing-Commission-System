<?php
namespace App\Console\Commands;

use App\Models\SubaccountDataBackup;
use App\Models\UserSetting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubaccountDataBackups extends Command
{
    protected $signature   = 'subaccount:process-backups';
    protected $description = 'Process subaccount data backups daily to update UserSettings';

    public function handle()
    {
        $today = now()->format('Y-m-d');

        $backups = SubaccountDataBackup::whereDate('created_at', $today)->get();

        foreach ($backups as $backup) {
            $email          = $backup->email;
            $subaccountData = $backup->data;

            $userSetting = UserSetting::where('email', $email)->first();

            if ($userSetting && ! $userSetting->contact_id) { //  // added && !$userSetting->contact_id in
                $updateData = [];

                // Update stripe data
                if (isset($subaccountData['stripeData'])) {
                    $updateData['stripe_payment_method_id'] = $subaccountData['stripeData']['stripe_payment_method_id'] ?? null;
                    $updateData['stripe_customer_id']       = $subaccountData['stripeData']['stripe_customer_id'] ?? null;
                }

                // Update contact_id from orderData
                if (isset($subaccountData['orderData']['contactId'])) {
                    $updateData['contact_id'] = $subaccountData['orderData']['contactId'];
                    //TODO: other contact fields that we added after this
                }

                // Update threshold, currency, price_id from priceMappingData
                if (isset($subaccountData['priceMappingData'])) {
                    $priceMapping = $subaccountData['priceMappingData']; // reset($subaccountData['priceMappingData']); // Get the first price mapping

                    $updateData['threshold_amount']      = $priceMapping['threshold_amount'] ?? null;
                    $updateData['currency']              = $priceMapping['currency'];
                    $updateData['price_id']              = $priceMapping['price_id'] ?? null;
                    $updateData['amount_charge_percent'] = $priceMapping['amount_charge_percent'] ?? 2;
                }

                $userSetting->update($updateData);

                Log::info('Updated UserSetting from backup', ['email' => $email]);

                // Optional: Delete the backup after processing
                $backup->delete();
            } else {
                Log::warning('No UserSetting found for email in backup', ['email' => $email]);
            }
        }

        $this->info('Subaccount data backups processed successfully');
    }
}
