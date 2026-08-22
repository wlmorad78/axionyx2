<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleMaintenancePlanController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Maintenance Plan
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Maintenance Plan" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenancePlan;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenancePlanController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Maintenance Plan) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleMaintenancePlan::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('plan_name', 'like', "%{$s}%")
                  ->orWhere('maintenance_type', 'like', "%{$s}%");
            });
        }

        if ($vehicleId = $request->input('vehicle_id')) {
            $query->where('vehicle_id', $vehicleId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Maintenance Plan) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_plan', 'create'));
        $item = VehicleMaintenancePlan::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleMaintenancePlan::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleMaintenancePlan::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_plan', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Maintenance Plan) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleMaintenancePlan::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Maintenance Plan) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleMaintenancePlan::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Maintenance Plan) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleMaintenancePlan::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
