<?php
namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateRefreshToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout       = 10000;
    public $failOnTimeout = false;

    protected $token;
    protected $type;
    protected $retries;

    /**
     * Create a new job instance.
     */
    public function __construct($token, $type = 'crm', $retries = 4)
    {
        $this->token   = $token;
        $this->type    = $type;
        $this->retries = $retries;
    }

    public function backoff(): int | array
    {
        return $this->timeout + 120; // retry after 2 minutes of $timeout
    }

    /**
     * Execute the job.
     */
    public function reAdd()
    {
        dispatch((new UpdateRefreshToken($this->token, $this->type))->onQueue(config('queue.type.refresh'))->delay(Carbon::now()->addMinutes(5)));
    }
    public function handle(): void
    {
        try {
            $rf = $this->token; //CrmToken::where('user_id',$this->userId)->first();
            if ($rf) {
                if ($this->type == 'crm') {
                    $status = $rf->urefresh();
                    if ($status == 500) {
                        // $this->reAdd();
                    }
                }
            }
        } catch (\Throwable $th) {
            // throw $th;
        }
    }
}
