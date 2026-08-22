<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleReservationController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Reservation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Reservation" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleReservation};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleReservationController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Reservation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleReservation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('purpose', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Reservation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_reservation', 'create'));
        $item = VehicleReservation::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleReservation::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleReservation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_reservation', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Reservation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleReservation::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Reservation) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleReservation::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Reservation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleReservation::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
