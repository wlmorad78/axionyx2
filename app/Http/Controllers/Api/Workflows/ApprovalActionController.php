<?php
/**
 * =====================================================================
 * متحكم (Controller): ApprovalActionController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Approval Action
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Approval Action" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\ApprovalAction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApprovalActionController extends Controller
{
    /**
     * عرض قائمة سجلات (Approval Action) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApprovalAction::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('action', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Approval Action) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('approval_action', 'create'));
        $approvalAction = ApprovalAction::create($data);
        return response()->json($approvalAction, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Approval Action) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return ApprovalAction::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Approval Action) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $approvalAction = ApprovalAction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('approval_action', 'update', $approvalAction));
        $approvalAction->update($data);
        return $approvalAction;
    }

    /**
     * حذف سجل من (Approval Action) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $approvalAction = ApprovalAction::findOrFail($id);
        $approvalAction->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Approval Action) وإعادته للعمل.
     */
    public function restore($id)
    {
        $approvalAction = ApprovalAction::withTrashed()->findOrFail($id);
        $approvalAction->restore();
        return $approvalAction;
    }

    /**
     * حذف نهائي للسجل من (Approval Action) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $approvalAction = ApprovalAction::withTrashed()->findOrFail($id);
        $approvalAction->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
