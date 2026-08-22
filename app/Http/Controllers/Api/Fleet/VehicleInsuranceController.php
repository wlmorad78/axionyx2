<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleInsuranceController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Insurance
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Insurance" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{VehicleInsurance};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleInsuranceController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Insurance) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleInsurance::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('policy_number', 'like', "%{$s}%")
                    ->orWhere('insurance_company', 'like', "%{$s}%");
            });
        }
        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Insurance) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_insurance', 'create'));
        $item = VehicleInsurance::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return VehicleInsurance::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = VehicleInsurance::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_insurance', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Insurance) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleInsurance::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Insurance) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleInsurance::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Insurance) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleInsurance::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
