<?php
/**
 * =====================================================================
 * متحكم (Controller): DriverTrainingController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Driver Training
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Driver Training" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverTraining};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverTrainingController extends Controller
{
    /**
     * عرض قائمة سجلات (Driver Training) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DriverTraining::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('training_name', 'like', "%{$s}%")
                  ->orWhere('training_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Driver Training) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_training', 'create'));
        $item = DriverTraining::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverTraining::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverTraining::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_training', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Driver Training) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = DriverTraining::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Driver Training) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = DriverTraining::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    /**
     * حذف نهائي للسجل من (Driver Training) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = DriverTraining::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
