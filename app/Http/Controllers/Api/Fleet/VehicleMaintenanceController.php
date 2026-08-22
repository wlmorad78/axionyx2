<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleMaintenanceController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Maintenance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Maintenance" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMaintenance;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMaintenanceController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Maintenance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleMaintenance::with(['vehicle']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('date_from')) {
            $query->where('maintenance_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('maintenance_date', '<=', $request->date_to);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('maintenance_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Maintenance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_maintenance', 'create'));
        $vehicleMaintenance = VehicleMaintenance::create($data);
        return response()->json($vehicleMaintenance, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Maintenance) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleMaintenance::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Maintenance) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicleMaintenance = VehicleMaintenance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_maintenance', 'update', $vehicleMaintenance));
        $vehicleMaintenance->update($data);
        return $vehicleMaintenance;
    }

    /**
     * حذف سجل من (Vehicle Maintenance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicleMaintenance = VehicleMaintenance::findOrFail($id);
        $vehicleMaintenance->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Maintenance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicleMaintenance = VehicleMaintenance::withTrashed()->findOrFail($id);
        $vehicleMaintenance->restore();
        return $vehicleMaintenance;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Maintenance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicleMaintenance = VehicleMaintenance::withTrashed()->findOrFail($id);
        $vehicleMaintenance->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
