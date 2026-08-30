<?php
/**
 * =====================================================================
 * متحكم (Controller): AttendanceRecordController
 * الوحدة (Module): الموارد البشرية (HR)
 * المورد (Resource): Attendance Record
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Attendance Record" ضمن وحدة "الموارد البشرية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\HR;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    /**
     * عرض قائمة سجلات (Attendance Record) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = AttendanceRecord::with($with);

        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->attendance_status_id) $query->where('attendance_status_id', $request->attendance_status_id);
        if ($request->shift_id) $query->where('shift_id', $request->shift_id);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('notes', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Attendance Record) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('attendance_record', 'store'));

        return response()->json(AttendanceRecord::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Attendance Record) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(AttendanceRecord $attendanceRecord)
    {
        return $attendanceRecord->load(['employee', 'shift', 'attendanceStatus']);
    }

    /**
     * تحديث بيانات سجل موجود من (Attendance Record) بناءً على المعرّف.
     */
    public function update(Request $request, AttendanceRecord $attendanceRecord)
    {
        $data = $request->validate(ValidationRules::for('attendance_record', 'update', $attendanceRecord));

        $attendanceRecord->update($data);

        return response()->json($attendanceRecord);
    }

    /**
     * حذف سجل من (Attendance Record) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Attendance Record) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $attendanceRecord = AttendanceRecord::onlyTrashed()->findOrFail($id);
        $attendanceRecord->restore();

        return response()->json($attendanceRecord);
    }

    /**
     * حذف نهائي للسجل من (Attendance Record) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        AttendanceRecord::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Attendance Record).
     */
    public function schema()
    {
        return ValidationRules::for('attendance_record', 'store');
    }
}
