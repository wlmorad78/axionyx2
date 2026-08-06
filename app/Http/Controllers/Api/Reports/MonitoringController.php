<?php

namespace App\Http\Controllers\Api\Reports;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;

class MonitoringController extends Controller
{
    // ============================================================
    // QUEUE JOBS
    // ============================================================

    /**
     * GET /api/monitoring/queue/stats
     */
    public function queueStats()
    {
        return response()->json(['data' => MonitoringService::getQueueStats()]);
    }

    /**
     * GET /api/monitoring/queue/jobs
     */
    public function queueJobs(Request $request)
    {
        $status = $request->query('status');
        $limit = $request->query('limit', 50);
        return response()->json(['data' => MonitoringService::getRecentJobs($status, $limit)]);
    }

    /**
     * POST /api/monitoring/queue/jobs/{id}/retry
     */
    public function queueRetry(int $id)
    {
        $result = MonitoringService::retryJob($id);
        if (!$result) {
            return response()->json(['message' => 'Job not found or not failed'], 404);
        }
        return response()->json(['message' => 'Job queued for retry']);
    }

    /**
     * DELETE /api/monitoring/queue/clear
     */
    public function queueClear(Request $request)
    {
        $days = $request->query('days', 7);
        $deleted = MonitoringService::clearOldJobs($days);
        return response()->json(['deleted' => $deleted]);
    }

    // ============================================================
    // SCHEDULED TASKS
    // ============================================================

    /**
     * GET /api/monitoring/tasks
     */
    public function tasks()
    {
        return response()->json(['data' => MonitoringService::getScheduledTasks()]);
    }

    /**
     * POST /api/monitoring/tasks/{command}/run
     */
    public function taskRun(string $command)
    {
        $result = MonitoringService::runTask($command);
        return response()->json(['data' => $result]);
    }

    // ============================================================
    // SYSTEM HEALTH
    // ============================================================

    /**
     * GET /api/monitoring/health
     */
    public function health()
    {
        return response()->json(['data' => MonitoringService::getHealth()]);
    }

    // ============================================================
    // ACTIVITY LOG
    // ============================================================

    /**
     * GET /api/monitoring/activity
     */
    public function activity(Request $request)
    {
        $type = $request->query('type');
        $limit = $request->query('limit', 50);
        return response()->json(['data' => MonitoringService::getRecentActivity($limit, $type)]);
    }

    /**
     * GET /api/monitoring/activity/stats
     */
    public function activityStats()
    {
        return response()->json(['data' => MonitoringService::getActivityStats()]);
    }
}
