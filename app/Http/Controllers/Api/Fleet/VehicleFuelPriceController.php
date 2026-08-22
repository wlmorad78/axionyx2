<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleFuelPriceController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Fuel Price
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Fuel Price" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleFuelPrice};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleFuelPriceController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Fuel Price) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleFuelPrice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('fuel_type', 'like', "%{$s}%");
            });
        }
        if ($request->has('fuel_station_id')) {
            $query->where('fuel_station_id', $request->input('fuel_station_id'));
        }
        if ($request->has('fuel_type')) {
            $query->where('fuel_type', $request->input('fuel_type'));
        }
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Vehicle Fuel Price) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_fuel_price', 'create'));
        $item = VehicleFuelPrice::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return VehicleFuelPrice::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = VehicleFuelPrice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_fuel_price', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Vehicle Fuel Price) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleFuelPrice::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
