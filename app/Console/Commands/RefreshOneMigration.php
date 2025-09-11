<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class RefreshOneMigration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan migrate:refresh-one {table}
     */
    protected $signature = 'migrate:refresh-one {table}';

    /**
     * The console command description.
     */
    protected $description = 'Drop, delete migration record, and re-run migration for a single table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $table = $this->argument('table');

        // Find migration file for the given table
        $migrationFile = collect(File::files(database_path('migrations')))
            ->first(fn($file) => str_contains($file->getFilename(), $table));

        if (! $migrationFile) {
            $this->error("Migration file for table '{$table}' not found!");
            return Command::FAILURE;
        }

        // Extract migration name from filename
        $migrationName = pathinfo($migrationFile->getFilename(), PATHINFO_FILENAME);

        $this->info("Refreshing migration for table: {$table}");
        $this->line("Migration file: {$migrationFile->getFilename()}");

        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Drop table if exists
            if (Schema::hasTable($table)) {
                Schema::drop($table);
                $this->info("Dropped table: {$table}");
            } else {
                $this->warn("Table '{$table}' does not exist, skipping drop.");
            }

            // Delete migration record from migrations table
            DB::table('migrations')->where('migration', $migrationName)->delete();
            $this->info("Deleted migration record: {$migrationName}");

            // Re-run the migration
            $this->call('migrate', [
                '--path' => 'database/migrations/' . $migrationFile->getFilename(),
            ]);

            $this->info("Migration for '{$table}' refreshed successfully!");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return Command::FAILURE;
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        return Command::SUCCESS;
    }
}
