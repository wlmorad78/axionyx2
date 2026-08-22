<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleBatteryController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Battery
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Battery" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleBattery};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleBatteryController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Battery) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleBattery::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('serial_number', 'like', "%{$s}%")
                  ->orWhere('brand', 'like', "%{$s}%");
            });
        }
        if ($request->has('vehicle_id')) {
            $query->where('vehicle_id', $request->input('vehicle_id'));
        }
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Vehicle Battery) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_battery', 'create'));
        $item = VehicleBattery::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleBattery::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleBattery::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_battery', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Vehicle Battery) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleBattery::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Battery) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleBattery::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    /**
     * حذف نهائي للسجل من (Vehicle Battery) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleBattery::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
