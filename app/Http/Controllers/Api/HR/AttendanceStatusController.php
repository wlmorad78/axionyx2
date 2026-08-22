<?php
/**
 * =====================================================================
 * متحكم (Controller): AttendanceStatusController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Attendance Status
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Attendance Status" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceStatus;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceStatusController extends Controller
{
    /**
     * عرض قائمة سجلات (Attendance Status) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceStatus::with($with);

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
     * إنشاء سجل جديد لـ (Attendance Status) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_status', 'store'));

        return response()->json(AttendanceStatus::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Attendance Status) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AttendanceStatus $attendanceStatus)
    {
        return $attendanceStatus;
    }

    /**
     * تحديث بيانات سجل موجود من (Attendance Status) بناءً على المعرّف.
     */
    public function update(Request $request, AttendanceStatus $attendanceStatus)
    {
        $data = $request->validate(ValidationRules::for('attendance_status', 'update', $attendanceStatus));

        $attendanceStatus->update($data);

        return response()->json($attendanceStatus);
    }

    /**
     * حذف سجل من (Attendance Status) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AttendanceStatus $attendanceStatus)
    {
        if ($attendanceStatus->is_system) {
            return response()->json(['message' => 'Cannot delete system record'], 403);
        }

        $attendanceStatus->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Attendance Status) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $attendanceStatus = AttendanceStatus::onlyTrashed()->findOrFail($id);
        $attendanceStatus->restore();

        return response()->json($attendanceStatus);
    }

    /**
     * حذف نهائي للسجل من (Attendance Status) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AttendanceStatus::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Attendance Status).
     */
    public function schema()
    {
        return ValidationRules::for('attendance_status', 'store');
    }
}
