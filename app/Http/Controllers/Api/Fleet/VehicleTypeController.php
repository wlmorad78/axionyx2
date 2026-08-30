<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleTypeController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Type" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleType::query();

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->input('company_id'));
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%")
                    ->orWhere('description', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->boolean('trashed')) {
            $query->withTrashed();
        }

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderBy('sort_order')->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_type', 'create'));
        if (empty($data['company_id']) && $request->filled('company_id')) {
            $data['company_id'] = $request->input('company_id');
        }
        $vehicleType = VehicleType::create($data);
        return response()->json($vehicleType, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleType::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Type) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_type', 'update', $vehicleType));
        $vehicleType->update($data);
        return $vehicleType;
    }

    /**
     * حذف سجل من (Vehicle Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicleType = VehicleType::findOrFail($id);
        $vehicleType->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Type) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicleType = VehicleType::withTrashed()->findOrFail($id);
        $vehicleType->restore();
        return $vehicleType;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicleType = VehicleType::withTrashed()->findOrFail($id);
        $vehicleType->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
