<?php
/**
 * =====================================================================
 * متحكم (Controller): AlertActionController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Alert Action
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Alert Action" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\AlertAction;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertActionController extends Controller
{
    /**
     * عرض قائمة سجلات (Alert Action) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AlertAction::with(['alert', 'actionBy']);

        if ($request->filled('alert_id')) $query->where('alert_id', $request->alert_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Alert Action) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert_action', 'create'));
        return response()->json(AlertAction::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Alert Action) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return AlertAction::with(['alert', 'actionBy'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Alert Action) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = AlertAction::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert_action', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Alert Action) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        AlertAction::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Alert Action) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = AlertAction::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Alert Action) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        AlertAction::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
