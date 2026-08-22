<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataRequestController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Request
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Request" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\MasterDataRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataRequestController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Request) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataRequest::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%{$s}%")
                  ->orWhere('entity_type', 'like', "%{$s}%")
                  ->orWhere('request_action', 'like', "%{$s}%")
                  ->orWhere('current_status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('current_status')) $query->where('current_status', $request->current_status);
        if ($request->filled('request_action')) $query->where('request_action', $request->request_action);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Request) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_request', 'create'));
        $masterDataRequest = MasterDataRequest::create($data);
        return response()->json($masterDataRequest, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Request) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataRequest::with(['steps', 'history', 'requestType', 'requestedBy'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Request) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataRequest = MasterDataRequest::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_request', 'update', $masterDataRequest));
        $masterDataRequest->update($data);
        return $masterDataRequest;
    }

    /**
     * حذف سجل من (Master Data Request) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataRequest = MasterDataRequest::findOrFail($id);
        $masterDataRequest->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Request) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataRequest = MasterDataRequest::withTrashed()->findOrFail($id);
        $masterDataRequest->restore();
        return $masterDataRequest;
    }

    /**
     * حذف نهائي للسجل من (Master Data Request) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataRequest = MasterDataRequest::withTrashed()->findOrFail($id);
        $masterDataRequest->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
