<?php
namespace App\Jobs;

use App\Jobs\UpdateRefreshToken;
use App\Models\CrmToken;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessRefreshToken implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout       = 10000;
    public $failOnTimeout = false;

    protected $page;

    /**
     * Create a new job instance.
     */
    public function __construct($page = 1)
    {
        $this->page = $page;
    }

    public function backoff(): int | array
    {
        return $this->timeout + 120; // retry after 2 minutes of $timeout
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $limit = 40;

            $currentPage = $this->page - 1;
            $skip        = $currentPage * $limit;
            $env         = config('queue.type.refresh');
            $rl          = CrmToken::skip($skip)->take($limit)->get();
            if ($rl->isNotEmpty()) {
                foreach ($rl as $r) {
                    dispatch((new UpdateRefreshToken($r))->onQueue($env)->delay(Carbon::now()->addSeconds(2)));
                }
                dispatch((new ProcessRefreshToken($this->page + 1))->onQueue($env)->delay(Carbon::now()->addSeconds(2)));
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }
}
