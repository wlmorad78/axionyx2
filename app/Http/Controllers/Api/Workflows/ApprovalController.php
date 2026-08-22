<?php
/**
 * =====================================================================
 * متحكم (Controller): ApprovalController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Approval
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Approval" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\ApprovalRequest;
use App\Models\ApprovalAction;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * GET /api/approvals
     * List pending approvals for current user.
     */
    public function index(Request $request)
    {
        $query = ApprovalRequest::with(['requestedBy:id,name', 'workflowDefinition:id,name'])
            ->where('status', 'pending');

        if ($request->has('status') && $request->status !== 'pending') {
            $query->where('status', $request->status);
        }

        if ($request->has('type')) {
            $query->where('reference_type', $request->type);
        }

        $approvals = $query->latest()->paginate($request->get('per_page', 20));
        return response()->json($approvals);
    }

    /**
     * GET /api/approvals/{id}
     */
    public function show(ApprovalRequest $approval)
    {
        $approval->load(['requestedBy:id,name', 'workflowDefinition', 'actions.user:id,name', 'actions.workflowStep']);
        return response()->json(['data' => $approval]);
    }

    /**
     * POST /api/approvals/{id}/approve
     */
    public function approve(Request $request, ApprovalRequest $approval)
    {
        $request->validate(['notes' => 'nullable|string']);

        ApprovalAction::create([
            'approval_request_id' => $approval->id,
            'workflow_step_id' => $approval->current_step,
            'user_id' => $request->user()->id,
            'action' => 'approved',
            'notes' => $request->notes,
            'action_date' => now(),
        ]);

        $approval->update(['status' => 'approved']);

        return response()->json(['message' => 'Approved']);
    }

    /**
     * POST /api/approvals/{id}/reject
     */
    public function reject(Request $request, ApprovalRequest $approval)
    {
        $request->validate(['notes' => 'required|string']);

        ApprovalAction::create([
            'approval_request_id' => $approval->id,
            'workflow_step_id' => $approval->current_step,
            'user_id' => $request->user()->id,
            'action' => 'rejected',
            'notes' => $request->notes,
            'action_date' => now(),
        ]);

        $approval->update(['status' => 'rejected']);

        return response()->json(['message' => 'Rejected']);
    }

    /**
     * GET /api/approvals/stats
     */
    public function stats(Request $request)
    {
        $base = ApprovalRequest::where('requested_by', $request->user()->id);

        return response()->json([
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'approved' => (clone $base)->where('status', 'approved')->count(),
            'rejected' => (clone $base)->where('status', 'rejected')->count(),
            'total' => (clone $base)->count(),
        ]);
    }
}
