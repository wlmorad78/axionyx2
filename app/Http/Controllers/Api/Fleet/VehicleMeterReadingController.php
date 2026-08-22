<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleMeterReadingController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Meter Reading
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Meter Reading" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleMeterReading;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleMeterReadingController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Meter Reading) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleMeterReading::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('reading_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('reading_type')) $query->where('reading_type', $request->reading_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Meter Reading) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_meter_reading', 'create'));
        $item = VehicleMeterReading::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Meter Reading) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleMeterReading::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Meter Reading) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleMeterReading::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_meter_reading', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Meter Reading) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleMeterReading::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Meter Reading) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleMeterReading::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Meter Reading) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleMeterReading::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
