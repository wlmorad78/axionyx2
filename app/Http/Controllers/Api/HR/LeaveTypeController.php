<?php
/**
 * =====================================================================
 * متحكم (Controller): LeaveTypeController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Leave Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Leave Type" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\LeaveType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeaveTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Leave Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LeaveType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Leave Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('leave_type', 'store'));

        return response()->json(LeaveType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Leave Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(LeaveType $leaveType)
    {
        return $leaveType;
    }

    /**
     * تحديث بيانات سجل موجود من (Leave Type) بناءً على المعرّف.
     */
    public function update(Request $request, LeaveType $leaveType)
    {
        $data = $request->validate(ValidationRules::for('leave_type', 'update', $leaveType));

        $leaveType->update($data);

        return response()->json($leaveType);
    }

    /**
     * حذف سجل من (Leave Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(LeaveType $leaveType)
    {
        $leaveType->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Leave Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $leaveType = LeaveType::onlyTrashed()->findOrFail($id);
        $leaveType->restore();

        return response()->json($leaveType);
    }

    /**
     * حذف نهائي للسجل من (Leave Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        LeaveType::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Leave Type).
     */
    public function schema()
    {
        return ValidationRules::for('leave_type', 'store');
    }
}
