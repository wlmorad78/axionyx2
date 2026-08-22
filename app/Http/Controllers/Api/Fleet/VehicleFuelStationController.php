<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleFuelStationController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Fuel Station
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Fuel Station" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleFuelStation};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelStationController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Fuel Station) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleFuelStation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('location', 'like', "%{$s}%");
            });
        }
        if ($request->has('is_active')) {
            $query->where('is_active', $request->input('is_active'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Vehicle Fuel Station) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_station', 'create'));
        $item = VehicleFuelStation::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleFuelStation::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleFuelStation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_station', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Vehicle Fuel Station) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleFuelStation::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Fuel Station) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleFuelStation::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    /**
     * حذف نهائي للسجل من (Vehicle Fuel Station) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleFuelStation::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
