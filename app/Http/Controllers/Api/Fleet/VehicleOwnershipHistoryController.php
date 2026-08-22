<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleOwnershipHistoryController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Ownership History
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Ownership History" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleOwnershipHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleOwnershipHistoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Ownership History) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleOwnershipHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('owner_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Ownership History) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_ownership_history', 'create'));
        $item = VehicleOwnershipHistory::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Ownership History) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleOwnershipHistory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Ownership History) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleOwnershipHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_ownership_history', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Ownership History) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleOwnershipHistory::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Ownership History) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleOwnershipHistory::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Ownership History) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleOwnershipHistory::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
