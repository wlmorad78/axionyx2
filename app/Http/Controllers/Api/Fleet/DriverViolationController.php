<?php
/**
 * =====================================================================
 * متحكم (Controller): DriverViolationController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Driver Violation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Driver Violation" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverViolation};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverViolationController extends Controller
{
    /**
     * عرض قائمة سجلات (Driver Violation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DriverViolation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('violation_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Driver Violation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_violation', 'create'));
        $item = DriverViolation::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverViolation::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverViolation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_violation', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Driver Violation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = DriverViolation::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Driver Violation) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = DriverViolation::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    /**
     * حذف نهائي للسجل من (Driver Violation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = DriverViolation::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
