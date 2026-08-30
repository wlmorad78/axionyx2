<?php
/**
 * =====================================================================
 * متحكم (Controller): AuditLogController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Audit Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Audit Log" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * GET /api/audit-logs
     * Paginated audit logs with filters.
     */
    public function index(Request $request)
    {
        $query = AuditLog::with(['user:id,name,email', 'company:id,name_en,name_ar'])
            ->where('company_id', $request->user()->company_id);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('table_name')) {
            $query->where('table_name', $request->table_name);
        }

        if ($request->has('action_type')) {
            $query->where('action_type', $request->action_type);
        }

        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }

        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('table_name', 'like', "%{$search}%")
                  ->orWhere('action_type', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate($request->get('per_page', 25));

        return response()->json($logs);
    }

    /**
     * GET /api/audit-logs/{id}
     * Single audit log with full details.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load(['user:id,name,email', 'company:id,name_en,name_ar', 'branch:id,name']);

        return response()->json(['data' => $auditLog]);
    }

    /**
     * GET /api/audit-logs/stats
     * Audit log statistics.
     */
    public function stats(Request $request)
    {
        $companyId = $request->user()->company_id;
        $query = AuditLog::where('company_id', $companyId);

        $total = $query->count();
        $today = (clone $query)->whereDate('created_at', today())->count();
        $thisWeek = (clone $query)->where('created_at', '>=', now()->startOfWeek())->count();
        $thisMonth = (clone $query)->where('created_at', '>=', now()->startOfMonth())->count();

        $byAction = (clone $query)
            ->selectRaw('action_type, COUNT(*) as count')
            ->groupBy('action_type')
            ->pluck('count', 'action_type')
            ->toArray();

        $byTable = (clone $query)
            ->selectRaw('table_name, COUNT(*) as count')
            ->groupBy('table_name')
            ->orderByDesc('count')
            ->take(10)
            ->pluck('count', 'table_name')
            ->toArray();

        $byUser = (clone $query)
            ->selectRaw('user_id, COUNT(*) as count')
            ->groupBy('user_id')
            ->orderByDesc('count')
            ->take(10)
            ->get()
            ->map(fn($row) => [
                'user_id' => $row->user_id,
                'user_name' => \App\Models\User::find($row->user_id)?->name ?? 'N/A',
                'count' => $row->count,
            ])
            ->toArray();

        return response()->json([
            'total' => $total,
            'today' => $today,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth,
            'by_action' => $byAction,
            'by_table' => $byTable,
            'by_user' => $byUser,
        ]);
    }
}
