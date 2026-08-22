<?php
/**
 * =====================================================================
 * متحكم (Controller): ApprovalRequestController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Approval Request
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Approval Request" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\ApprovalRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApprovalRequestController extends Controller
{
    /**
     * عرض قائمة سجلات (Approval Request) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApprovalRequest::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('reference_type', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Approval Request) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('approval_request', 'create'));
        $approvalRequest = ApprovalRequest::create($data);
        return response()->json($approvalRequest, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Approval Request) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ApprovalRequest::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Approval Request) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('approval_request', 'update', $approvalRequest));
        $approvalRequest->update($data);
        return $approvalRequest;
    }

    /**
     * حذف سجل من (Approval Request) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $approvalRequest = ApprovalRequest::findOrFail($id);
        $approvalRequest->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Approval Request) وإعادته للعمل.
     */
    public function restore($id)
    {
        $approvalRequest = ApprovalRequest::withTrashed()->findOrFail($id);
        $approvalRequest->restore();
        return $approvalRequest;
    }

    /**
     * حذف نهائي للسجل من (Approval Request) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $approvalRequest = ApprovalRequest::withTrashed()->findOrFail($id);
        $approvalRequest->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
