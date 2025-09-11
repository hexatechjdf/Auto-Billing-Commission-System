<?php
namespace App\Jobs;

use App\Helper\CRM;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

// adjust namespace if needed

class ConnectSubaccountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected $companyId, protected $locationId, protected $dbUserId = null)
    {

    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $companyToken = CRM::getCrmToken([
            'company_id' => $this->companyId,
            'user_type'  => CRM::$lang_com,
        ]);

        $companyUserId = $companyToken->user_id ?? null;

        if (! $companyUserId) {
            Log::error('No user found for company ID', ['companyId' => $this->companyId]);
            return;
        }

        if (! $this->dbUserId) {
            list($isNewlyCreated, $dbUser) = findOrCreateUserInDb($this->locationId);

            if (! $dbUser) {
                return;
            }

            $dbUserId = $dbUser->id;
        }

        $token = CRM::getLocationAccessToken($companyUserId, $this->locationId, $companyToken, 0, $this->dbUserId);

        if ($token) {
            Log::info('Connected subaccount for location', [
                'locationId' => $this->locationId,
                'tokenData'  => $token,
            ]);
        } else {
            Log::error('Failed to connect subaccount', [
                'locationId' => $this->locationId,
                'tokenData'  => $token,
            ]);
        }
    }
}
