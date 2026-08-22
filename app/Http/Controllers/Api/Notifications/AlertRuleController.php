<?php
/**
 * =====================================================================
 * متحكم (Controller): AlertRuleController
 * الوحدة (Module): الإشعارات والتنبيهات (Notifications)
 * المورد (Resource): Alert Rule
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Alert Rule" ضمن وحدة "الإشعارات والتنبيهات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notifications\AlertRule;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AlertRuleController extends Controller
{
    /**
     * عرض قائمة سجلات (Alert Rule) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AlertRule::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('alert_code', 'like', "%{$s}%")
                    ->orWhere('alert_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        if ($request->filled('severity')) $query->where('severity', $request->severity);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Alert Rule).
     */
    public function nextCode()
    {
        $last = AlertRule::orderByDesc('id')->value('alert_code');
        if ($last && preg_match('/ALR-(\d+)/', $last, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['code' => 'ALR-' . str_pad($next, 4, '0', STR_PAD_LEFT)]);
    }

    /**
     * إنشاء سجل جديد لـ (Alert Rule) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('alert_rule', 'create'));
        return response()->json(AlertRule::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Alert Rule) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return AlertRule::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Alert Rule) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = AlertRule::findOrFail($id);
        $data = $request->validate(ValidationRules::for('alert_rule', 'update', $model));
        $model->update($data);
        return $model;
    }

    /**
     * حذف سجل من (Alert Rule) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        AlertRule::findOrFail($id)->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Alert Rule) وإعادته للعمل.
     */
    public function restore($id)
    {
        $model = AlertRule::withTrashed()->findOrFail($id);
        $model->restore();
        return $model;
    }

    /**
     * حذف نهائي للسجل من (Alert Rule) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        AlertRule::withTrashed()->findOrFail($id)->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
