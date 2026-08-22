<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleTireInspectionController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Tire Inspection
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Tire Inspection" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleTireInspection};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleTireInspectionController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Tire Inspection) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleTireInspection::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('inspected_by', 'like', "%{$s}%");
            });
        }
        if ($request->has('tire_id')) {
            $query->where('tire_id', $request->input('tire_id'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Vehicle Tire Inspection) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_tire_inspection', 'create'));
        $item = VehicleTireInspection::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleTireInspection::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleTireInspection::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_tire_inspection', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Vehicle Tire Inspection) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleTireInspection::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
