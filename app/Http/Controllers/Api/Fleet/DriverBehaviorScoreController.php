<?php
/**
 * =====================================================================
 * متحكم (Controller): DriverBehaviorScoreController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Driver Behavior Score
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Driver Behavior Score" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverBehaviorScore};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverBehaviorScoreController extends Controller
{
    /**
     * عرض قائمة سجلات (Driver Behavior Score) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DriverBehaviorScore::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Driver Behavior Score) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_behavior_score', 'create'));
        $item = DriverBehaviorScore::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverBehaviorScore::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverBehaviorScore::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_behavior_score', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Driver Behavior Score) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = DriverBehaviorScore::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
