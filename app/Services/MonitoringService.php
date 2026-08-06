<?php

namespace App\Services;

use App\Models\QueueJob;
use App\Models\ScheduledTask;
use App\Models\SystemHealth;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class MonitoringService
{
    // ============================================================
    // QUEUE MONITOR
    // ============================================================

    /**
     * Get queue stats.
     */
    public static function getQueueStats(): array
    {
        $jobs = QueueJob::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $recentFailed = QueueJob::failed()
            ->latest()
            ->limit(10)
            ->get(['id', 'job_name', 'queue', 'error_message', 'attempts', 'created_at']);

        $avgDuration = QueueJob::completed()
            ->where('duration_ms', '>', 0)
            ->avg('duration_ms');

        $jobsPerQueue = QueueJob::select('queue', DB::raw('count(*) as count'))
            ->groupBy('queue')
            ->pluck('count', 'queue')
            ->toArray();

        return [
            'pending' => $jobs['pending'] ?? 0,
            'running' => $jobs['running'] ?? 0,
            'completed' => $jobs['completed'] ?? 0,
            'failed' => $jobs['failed'] ?? 0,
            'total' => array_sum($jobs),
            'avg_duration_ms' => round($avgDuration ?? 0, 2),
            'recent_failed' => $recentFailed,
            'jobs_per_queue' => $jobsPerQueue,
        ];
    }

    /**
     * Get recent jobs.
     */
    public static function getRecentJobs(string $status = null, int $limit = 50): array
    {
        $query = QueueJob::query();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Retry a failed job.
     */
    public static function retryJob(int $jobId): bool
    {
        $job = QueueJob::find($jobId);
        if (!$job || $job->status !== 'failed') return false;

        $job->update([
            'status' => 'pending',
            'attempts' => 0,
            'error_message' => null,
        ]);

        return true;
    }

    /**
     * Clear completed jobs older than X days.
     */
    public static function clearOldJobs(int $days = 7): int
    {
        return QueueJob::where('status', 'completed')
            ->where('created_at', '<', Carbon::now()->subDays($days))
            ->delete();
    }

    // ============================================================
    // SCHEDULED TASKS
    // ============================================================

    /**
     * Get all scheduled tasks.
     */
    public static function getScheduledTasks(): array
    {
        return ScheduledTask::all()->toArray();
    }

    /**
     * Run a scheduled task manually.
     */
    public static function runTask(string $command): array
    {
        $task = ScheduledTask::firstOrCreate(
            ['command' => $command],
            ['schedule' => null, 'status' => 'pending']
        );

        $task->update(['status' => 'running', 'started_at' => now(), 'output' => null, 'error_message' => null]);
        $start = microtime(true);

        try {
            $exitCode = Artisan::call($command);
            $output = Artisan::output();
            $duration = (int) ((microtime(true) - $start) * 1000);

            $task->update([
                'status' => $exitCode === 0 ? 'completed' : 'failed',
                'output' => $output,
                'exit_code' => $exitCode,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            return ['status' => $task->status, 'output' => $output, 'exit_code' => $exitCode, 'duration_ms' => $duration];
        } catch (\Exception $e) {
            $task->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'exit_code' => 1,
                'finished_at' => now(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Seed default scheduled tasks.
     */
    public static function seedDefaultTasks(): void
    {
        $tasks = [
            ['command' => 'axionyx:generate-constants', 'schedule' => '@daily', 'is_enabled' => true],
            ['command' => 'axionyx:module:cleanup', 'schedule' => '@weekly', 'is_enabled' => true],
            ['command' => 'queue:work --stop-when-empty', 'schedule' => '*/5 * * * *', 'is_enabled' => true],
        ];

        foreach ($tasks as $task) {
            ScheduledTask::updateOrCreate(
                ['command' => $task['command']],
                $task
            );
        }
    }

    // ============================================================
    // SYSTEM HEALTH
    // ============================================================

    /**
     * Get current system health.
     */
    public static function getHealth(): array
    {
        $snapshot = SystemHealth::latestSnapshot();

        // PHP info
        $phpVersion = phpversion();
        $laravelVersion = app()->version();

        // Database
        $dbSize = DB::select("SELECT (page_count * page_size) as size FROM pragma_page_count(), pragma_page_size()");
        $dbSizeMB = round(($dbSize[0]->size ?? 0) / 1024 / 1024, 2);

        // Disk
        $diskFree = disk_free_space('.') / 1024 / 1024 / 1024;
        $diskTotal = disk_total_space('.') / 1024 / 1024 / 1024;

        // Memory
        $memUsage = memory_get_usage(true) / 1024 / 1024;
        $memLimit = ini_get('memory_limit');

        return [
            'php_version' => $phpVersion,
            'laravel_version' => $laravelVersion,
            'db_size_mb' => $dbSizeMB,
            'disk_free_gb' => round($diskFree, 2),
            'disk_total_gb' => round($diskTotal, 2),
            'disk_used_percent' => round((1 - $diskFree / $diskTotal) * 100, 1),
            'memory_usage_mb' => round($memUsage, 2),
            'memory_limit' => $memLimit,
            'metrics' => $snapshot,
            'checks' => self::runHealthChecks(),
        ];
    }

    /**
     * Run health checks.
     */
    protected static function runHealthChecks(): array
    {
        $checks = [];

        // Database
        try {
            DB::getPdo();
            $checks[] = ['name' => 'Database', 'status' => 'healthy', 'message' => 'Connected'];
        } catch (\Exception $e) {
            $checks[] = ['name' => 'Database', 'status' => 'critical', 'message' => $e->getMessage()];
        }

        // Queue
        $pendingJobs = QueueJob::pending()->count();
        if ($pendingJobs > 100) {
            $checks[] = ['name' => 'Queue', 'status' => 'warning', 'message' => "{$pendingJobs} pending jobs"];
        } else {
            $checks[] = ['name' => 'Queue', 'status' => 'healthy', 'message' => "{$pendingJobs} pending jobs"];
        }

        // Failed jobs
        $failedJobs = QueueJob::failed()->count();
        if ($failedJobs > 10) {
            $checks[] = ['name' => 'Failed Jobs', 'status' => 'warning', 'message' => "{$failedJobs} failed jobs"];
        } else {
            $checks[] = ['name' => 'Failed Jobs', 'status' => 'healthy', 'message' => "{$failedJobs} failed jobs"];
        }

        // Disk
        $diskFree = disk_free_space('.') / 1024 / 1024 / 1024;
        if ($diskFree < 1) {
            $checks[] = ['name' => 'Disk Space', 'status' => 'critical', 'message' => "{$diskFree} GB free"];
        } elseif ($diskFree < 5) {
            $checks[] = ['name' => 'Disk Space', 'status' => 'warning', 'message' => "{$diskFree} GB free"];
        } else {
            $checks[] = ['name' => 'Disk Space', 'status' => 'healthy', 'message' => "{$diskFree} GB free"];
        }

        // Cache
        try {
            Cache::put('_health_check', true, 10);
            $checks[] = ['name' => 'Cache', 'status' => 'healthy', 'message' => 'Working'];
        } catch (\Exception $e) {
            $checks[] = ['name' => 'Cache', 'status' => 'critical', 'message' => 'Not available'];
        }

        return $checks;
    }

    // ============================================================
    // ACTIVITY LOG
    // ============================================================

    /**
     * Get recent activity.
     */
    public static function getRecentActivity(int $limit = 50, ?string $type = null): array
    {
        $query = ActivityLog::with('user:id,name');

        if ($type) {
            $query->where('type', $type);
        }

        return $query->latest()
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get activity stats by type.
     */
    public static function getActivityStats(): array
    {
        return ActivityLog::select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->pluck('count', 'type')
            ->toArray();
    }
}
