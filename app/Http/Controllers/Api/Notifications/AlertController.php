<?php
/**
 * =====================================================================
 * متحكم (Controller): AlertController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Alert
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Alert" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    /**
     * عرض قائمة سجلات (Alert) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Alert::with(['alertRule']);

        if ($request->filled('alert_rule_id')) $query->where('alert_rule_id', $request->alert_rule_id);
        if ($request->filled('severity')) $query->where('severity', $request->severity);
        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Alert) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert', 'create'));
        return response()->json(Alert::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Alert) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Alert::with(['alertRule'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Alert) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = Alert::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Alert) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        Alert::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Alert) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = Alert::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Alert) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        Alert::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
