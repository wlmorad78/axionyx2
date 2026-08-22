<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Fleet\Vehicle;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Vehicle::query();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('vehicle_code', 'like', "%{$s}%")
                    ->orWhere('plate_number', 'like', "%{$s}%")
                    ->orWhere('model', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle', 'create'));
        $vehicle = Vehicle::create($data);
        return response()->json($vehicle, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Vehicle::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle', 'update', $vehicle));
        $vehicle->update($data);
        return $vehicle;
    }

    /**
     * حذف سجل من (Vehicle) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicle = Vehicle::findOrFail($id);
        $vehicle->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicle = Vehicle::withTrashed()->findOrFail($id);
        $vehicle->restore();
        return $vehicle;
    }

    /**
     * حذف نهائي للسجل من (Vehicle) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicle = Vehicle::withTrashed()->findOrFail($id);
        $vehicle->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
