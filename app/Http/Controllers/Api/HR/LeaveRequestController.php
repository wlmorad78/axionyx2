<?php
/**
 * =====================================================================
 * متحكم (Controller): LeaveRequestController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Leave Request
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Leave Request" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeaveRequestController extends Controller
{
    /**
     * عرض قائمة سجلات (Leave Request) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LeaveRequest::with($with);

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->leave_type_id) {
            $query->where('leave_type_id', $request->leave_type_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('reason', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Leave Request) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('leave_request', 'store'));

        return response()->json(LeaveRequest::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Leave Request) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(LeaveRequest $leaveRequest)
    {
        return $leaveRequest->load(['employee', 'leaveType', 'approver']);
    }

    /**
     * تحديث بيانات سجل موجود من (Leave Request) بناءً على المعرّف.
     */
    public function update(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate(ValidationRules::for('leave_request', 'update', $leaveRequest));

        $leaveRequest->update($data);

        return response()->json($leaveRequest);
    }

    /**
     * حذف سجل من (Leave Request) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(LeaveRequest $leaveRequest)
    {
        $leaveRequest->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Leave Request) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $leaveRequest = LeaveRequest::onlyTrashed()->findOrFail($id);
        $leaveRequest->restore();

        return response()->json($leaveRequest);
    }

    /**
     * حذف نهائي للسجل من (Leave Request) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        LeaveRequest::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Leave Request).
     */
    public function schema()
    {
        return ValidationRules::for('leave_request', 'store');
    }
}
