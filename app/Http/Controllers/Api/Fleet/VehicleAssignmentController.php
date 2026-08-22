<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleAssignmentController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Assignment" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleAssignment::query();

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_assignment', 'create'));
        $vehicleAssignment = VehicleAssignment::create($data);
        return response()->json($vehicleAssignment, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleAssignment::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicleAssignment = VehicleAssignment::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_assignment', 'update', $vehicleAssignment));
        $vehicleAssignment->update($data);
        return $vehicleAssignment;
    }

    /**
     * حذف سجل من (Vehicle Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicleAssignment = VehicleAssignment::findOrFail($id);
        $vehicleAssignment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Assignment) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicleAssignment = VehicleAssignment::withTrashed()->findOrFail($id);
        $vehicleAssignment->restore();
        return $vehicleAssignment;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicleAssignment = VehicleAssignment::withTrashed()->findOrFail($id);
        $vehicleAssignment->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
