<?php
namespace App\Console\Commands;

use App\Models\Log as LogModel;
use Illuminate\Console\Command;

class DeleteOldLogs extends Command
{
    protected $signature   = 'logs:delete-old';
    protected $description = 'Delete logs older than 10 days';

    public function handle()
    {
        try {
            $cutoffDate   = subDays(10);
            $deletedCount = LogModel::where('created_at', '<', $cutoffDate)->delete();

            $this->info("Deleted {$deletedCount} logs older than 10 days.");
            // Log::info("Deleted {$deletedCount} logs older than 10 days.");
        } catch (\Exception $e) {
            $this->error("Failed to delete old logs: " . $e->getMessage());
            // Log::error("Failed to delete old logs: " . $e->getMessage());
        }
    }
}
