<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateTables extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:truncate {--force : Force truncate without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate jobs, recipes, recipe_requests, and failed_jobs tables';

    /**
     * Tables to truncate
     *
     * @var array
     */
    protected array $tables = [
        'jobs',
        'recipes',
        'recipe_requests',
        'failed_jobs',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete all data from: ' . implode(', ', $this->tables) . '. Continue?')) {
                $this->info('Truncate cancelled.');
                return self::SUCCESS;
            }
        }

        $this->info('Truncating tables...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach ($this->tables as $table) {
            try {
                DB::table($table)->truncate();
                $this->line("✓ Truncated: {$table}");
            } catch (\Exception $e) {
                $this->error("✗ Failed to truncate {$table}: {$e->getMessage()}");
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->info('Done!');

        return self::SUCCESS;
    }
}
