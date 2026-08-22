<?php
/**
 * =====================================================================
 * متحكم (Controller): MasterDataTypeController
 * الوحدة (Module): سير العمل والموافقات (Workflows)
 * المورد (Resource): Master Data Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Master Data Type" ضمن وحدة "سير العمل والموافقات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Workflows;

use App\Http\Controllers\Controller;
use App\Models\Workflows\MasterDataType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MasterDataTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Master Data Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MasterDataType::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                  ->orWhere('name', 'like', "%{$s}%")
                  ->orWhere('entity_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Master Data Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('master_data_type', 'create'));
        $masterDataType = MasterDataType::create($data);
        return response()->json($masterDataType, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Master Data Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MasterDataType::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Master Data Type) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $masterDataType = MasterDataType::findOrFail($id);
        $data = $request->validate(ValidationRules::for('master_data_type', 'update', $masterDataType));
        $masterDataType->update($data);
        return $masterDataType;
    }

    /**
     * حذف سجل من (Master Data Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $masterDataType = MasterDataType::findOrFail($id);
        $masterDataType->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Master Data Type) وإعادته للعمل.
     */
    public function restore($id)
    {
        $masterDataType = MasterDataType::withTrashed()->findOrFail($id);
        $masterDataType->restore();
        return $masterDataType;
    }

    /**
     * حذف نهائي للسجل من (Master Data Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $masterDataType = MasterDataType::withTrashed()->findOrFail($id);
        $masterDataType->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
