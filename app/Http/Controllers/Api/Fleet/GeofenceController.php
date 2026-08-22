<?php
/**
 * =====================================================================
 * متحكم (Controller): GeofenceController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Geofence
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Geofence" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Geofence;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class GeofenceController extends Controller
{
    /**
     * عرض قائمة سجلات (Geofence) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Geofence::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Geofence) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('geofence', 'create'));
        $item = Geofence::create($data);
        return response()->json($item, 201);
    }

    public function show($id) { return Geofence::findOrFail($id); }

    public function update(Request $request, $id)
    {
        $item = Geofence::findOrFail($id);
        $data = $request->validate(ValidationRules::for('geofence', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Geofence) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = Geofence::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Geofence) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = Geofence::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Geofence) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = Geofence::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
