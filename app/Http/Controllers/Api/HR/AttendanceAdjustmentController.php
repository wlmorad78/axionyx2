<?php
/**
 * =====================================================================
 * متحكم (Controller): AttendanceAdjustmentController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Attendance Adjustment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Attendance Adjustment" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceAdjustment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceAdjustmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Attendance Adjustment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceAdjustment::with($with);

        if ($request->attendance_record_id) $query->where('attendance_record_id', $request->attendance_record_id);

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Attendance Adjustment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_adjustment', 'store'));

        return response()->json(AttendanceAdjustment::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Attendance Adjustment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AttendanceAdjustment $attendanceAdjustment)
    {
        return $attendanceAdjustment->load(['attendanceRecord']);
    }

    /**
     * تحديث بيانات سجل موجود من (Attendance Adjustment) بناءً على المعرّف.
     */
    public function update(Request $request, AttendanceAdjustment $attendanceAdjustment)
    {
        $data = $request->validate(ValidationRules::for('attendance_adjustment', 'update', $attendanceAdjustment));

        $attendanceAdjustment->update($data);

        return response()->json($attendanceAdjustment);
    }

    /**
     * حذف سجل من (Attendance Adjustment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AttendanceAdjustment $attendanceAdjustment)
    {
        $attendanceAdjustment->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Attendance Adjustment) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $attendanceAdjustment = AttendanceAdjustment::onlyTrashed()->findOrFail($id);
        $attendanceAdjustment->restore();

        return response()->json($attendanceAdjustment);
    }

    /**
     * حذف نهائي للسجل من (Attendance Adjustment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AttendanceAdjustment::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Attendance Adjustment).
     */
    public function schema()
    {
        return ValidationRules::for('attendance_adjustment', 'store');
    }
}
