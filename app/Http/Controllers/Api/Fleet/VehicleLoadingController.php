<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleLoadingController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Loading
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Loading" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoading;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleLoadingController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Loading) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleLoading::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('loading_date', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Loading) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_loading', 'create'));
        $vehicleLoading = VehicleLoading::create($data);
        return response()->json($vehicleLoading, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Loading) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleLoading::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Loading) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $vehicleLoading = VehicleLoading::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_loading', 'update', $vehicleLoading));
        $vehicleLoading->update($data);
        return $vehicleLoading;
    }

    /**
     * حذف سجل من (Vehicle Loading) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $vehicleLoading = VehicleLoading::findOrFail($id);
        $vehicleLoading->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Loading) وإعادته للعمل.
     */
    public function restore($id)
    {
        $vehicleLoading = VehicleLoading::withTrashed()->findOrFail($id);
        $vehicleLoading->restore();
        return $vehicleLoading;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Loading) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $vehicleLoading = VehicleLoading::withTrashed()->findOrFail($id);
        $vehicleLoading->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
