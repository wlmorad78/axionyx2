<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleAlertController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Alert
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Alert" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleAlert;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleAlertController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Alert) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleAlert::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('alert_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('alert_type')) $query->where('alert_type', $request->alert_type);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('is_read') !== null) $query->where('is_read', $request->boolean('is_read'));
        if ($request->filled('is_resolved') !== null) $query->where('is_resolved', $request->boolean('is_resolved'));

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Alert) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_alert', 'create'));
        $alert = VehicleAlert::create($data);
        return response()->json($alert, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Alert) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleAlert::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Alert) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $alert = VehicleAlert::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_alert', 'update', $alert));
        $alert->update($data);
        return $alert;
    }

    /**
     * حذف سجل من (Vehicle Alert) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $alert = VehicleAlert::findOrFail($id);
        $alert->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Alert) وإعادته للعمل.
     */
    public function restore($id)
    {
        $alert = VehicleAlert::withTrashed()->findOrFail($id);
        $alert->restore();
        return $alert;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Alert) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $alert = VehicleAlert::withTrashed()->findOrFail($id);
        $alert->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }

    /**
     * دالة معالجة: markAsRead — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Vehicle Alert).
     */
    public function markAsRead($id)
    {
        $alert = VehicleAlert::findOrFail($id);
        $alert->update(['is_read' => true]);
        return $alert;
    }

    /**
     * دالة معالجة: resolve — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Vehicle Alert).
     */
    public function resolve(Request $request, $id)
    {
        $alert = VehicleAlert::findOrFail($id);
        $alert->update([
            'is_resolved' => true,
            'resolved_by' => $request->user()->id ?? null,
            'resolved_at' => now(),
        ]);
        return $alert;
    }
}
