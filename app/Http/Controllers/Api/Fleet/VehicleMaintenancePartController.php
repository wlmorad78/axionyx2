<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleMaintenancePartController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Maintenance Part
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Maintenance Part" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenancePart;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenancePartController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Maintenance Part) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleMaintenancePart::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($maintenanceId = $request->input('vehicle_maintenance_id')) {
            $query->where('vehicle_maintenance_id', $maintenanceId);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Maintenance Part) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_part', 'create'));
        $item = VehicleMaintenancePart::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleMaintenancePart::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleMaintenancePart::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance_part', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Maintenance Part) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleMaintenancePart::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
