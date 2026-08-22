<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataRequestHistoryController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Request History
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Request History" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequestHistory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestHistoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Request History) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataRequestHistory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('action_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('master_data_request_id')) $query->where('master_data_request_id', $request->master_data_request_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Request History) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request_history', 'create'));
        $masterDataRequestHistory = MasterDataRequestHistory::create($data);
        return response()->json($masterDataRequestHistory, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Request History) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataRequestHistory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Request History) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request_history', 'update', $masterDataRequestHistory));
        $masterDataRequestHistory->update($data);
        return $masterDataRequestHistory;
    }

    /**
     * حذف سجل من (Master Data Request History) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::findOrFail($id);
        $masterDataRequestHistory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Request History) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::withTrashed()->findOrFail($id);
        $masterDataRequestHistory->restore();
        return $masterDataRequestHistory;
    }

    /**
     * حذف نهائي للسجل من (Master Data Request History) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataRequestHistory = MasterDataRequestHistory::withTrashed()->findOrFail($id);
        $masterDataRequestHistory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
