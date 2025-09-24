<?php

namespace Yatilabs\ApiAccess\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Yatilabs\ApiAccess\Models\ApiLog;
use Carbon\Carbon;

class CleanupApiLogsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'api-access:cleanup-logs 
                           {--days= : Number of days to retain logs (defaults to config value)}
                           {--dry-run : Show what would be deleted without actually deleting}
                           {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old API access logs based on retention period';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isForced = $this->option('force');
        
        // Get retention days from option or config
        $retentionDays = $this->option('days') ?: config('api-access.logging.retention_days', 90);
        
        if (!is_numeric($retentionDays) || $retentionDays < 1) {
            $this->error('Retention days must be a positive number.');
            return self::FAILURE;
        }

        // Check if cleanup is enabled in config
        if (!config('api-access.logging.cleanup_enabled', true)) {
            $this->info('Log cleanup is disabled in configuration.');
            return self::SUCCESS;
        }

        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        // Count logs to be deleted
        $logsToDelete = ApiLog::olderThan($retentionDays)->count();
        
        if ($logsToDelete === 0) {
            $this->info('No logs found older than ' . $retentionDays . ' days.');
            return self::SUCCESS;
        }

        // Display information
        $this->info("Retention period: {$retentionDays} days");
        $this->info("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->info("Logs to be deleted: {$logsToDelete}");
        
        if ($isDryRun) {
            $this->info('DRY RUN: No logs were actually deleted.');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Retention Days', $retentionDays],
                    ['Cutoff Date', $cutoffDate->format('Y-m-d H:i:s')],
                    ['Logs to Delete', $logsToDelete],
                    ['Total Logs', ApiLog::count()],
                    ['Logs After Cleanup', ApiLog::count() - $logsToDelete],
                ]
            );
            return self::SUCCESS;
        }

        // Confirm deletion unless forced
        if (!$isForced) {
            if (!$this->confirm("Are you sure you want to delete {$logsToDelete} log entries?", false)) {
                $this->info('Operation cancelled.');
                return self::SUCCESS;
            }
        }

        // Perform cleanup
        $this->info('Starting cleanup...');
        
        $bar = $this->output->createProgressBar(100);
        $bar->start();
        
        try {
            // Delete in chunks to avoid memory issues
            $deletedCount = 0;
            $chunkSize = 1000;
            
            while (true) {
                $logsToDeleteChunk = ApiLog::olderThan($retentionDays)
                    ->limit($chunkSize)
                    ->pluck('id');
                
                if ($logsToDeleteChunk->isEmpty()) {
                    break;
                }
                
                ApiLog::whereIn('id', $logsToDeleteChunk)->delete();
                $deletedCount += $logsToDeleteChunk->count();
                
                // Update progress bar
                $progress = min(100, ($deletedCount / $logsToDelete) * 100);
                $bar->setProgress($progress);
                
                // Add a small delay to prevent overwhelming the database
                usleep(10000); // 10ms
            }
            
            $bar->finish();
            $this->newLine(2);
            
            $this->info("Successfully deleted {$deletedCount} log entries.");
            $this->info("Remaining logs: " . ApiLog::count());
            
            // Log the cleanup operation
            Log::info('API logs cleanup completed', [
                'deleted_count' => $deletedCount,
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
                'remaining_logs' => ApiLog::count(),
            ]);
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $bar->finish();
            $this->newLine();
            $this->error('Error during cleanup: ' . $e->getMessage());
            
            Log::error('API logs cleanup failed', [
                'error' => $e->getMessage(),
                'retention_days' => $retentionDays,
                'cutoff_date' => $cutoffDate->toDateTimeString(),
            ]);
            
            return self::FAILURE;
        }
    }
}