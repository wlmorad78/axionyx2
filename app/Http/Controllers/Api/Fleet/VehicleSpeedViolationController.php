<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleSpeedViolationController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Speed Violation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Speed Violation" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleSpeedViolation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleSpeedViolationController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Speed Violation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleSpeedViolation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Speed Violation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_speed_violation', 'create'));
        $item = VehicleSpeedViolation::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleSpeedViolation::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleSpeedViolation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_speed_violation', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Speed Violation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleSpeedViolation::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
