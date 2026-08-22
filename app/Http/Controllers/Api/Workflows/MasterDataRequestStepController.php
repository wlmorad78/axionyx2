<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataRequestStepController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Request Step
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Request Step" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequestStep;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestStepController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Request Step) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataRequestStep::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('master_data_request_id')) $query->where('master_data_request_id', $request->master_data_request_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Request Step) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request_step', 'create'));
        $masterDataRequestStep = MasterDataRequestStep::create($data);
        return response()->json($masterDataRequestStep, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Request Step) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataRequestStep::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Request Step) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataRequestStep = MasterDataRequestStep::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request_step', 'update', $masterDataRequestStep));
        $masterDataRequestStep->update($data);
        return $masterDataRequestStep;
    }

    /**
     * حذف سجل من (Master Data Request Step) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::findOrFail($id);
        $masterDataRequestStep->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Request Step) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::withTrashed()->findOrFail($id);
        $masterDataRequestStep->restore();
        return $masterDataRequestStep;
    }

    /**
     * حذف نهائي للسجل من (Master Data Request Step) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataRequestStep = MasterDataRequestStep::withTrashed()->findOrFail($id);
        $masterDataRequestStep->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
