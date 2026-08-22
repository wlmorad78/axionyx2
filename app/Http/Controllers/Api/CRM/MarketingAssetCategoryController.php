<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingAssetCategoryController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Asset Category
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Asset Category" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingAssetCategory;
use Illuminate\Http\Request;

class MarketingAssetCategoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Asset Category) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MarketingAssetCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%{$s}%")
                    ->orWhere('name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('is_active', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Asset Category) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:255|unique:marketing_asset_categories,code',
            'name' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category = MarketingAssetCategory::create($data);
        return response()->json($category, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Asset Category) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MarketingAssetCategory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Asset Category) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $category = MarketingAssetCategory::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'code' => 'sometimes|required|string|max:255|unique:marketing_asset_categories,code,' . $id,
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $category->update($data);
        return $category;
    }

    /**
     * حذف سجل من (Marketing Asset Category) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $category = MarketingAssetCategory::findOrFail($id);
        $category->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Asset Category) وإعادته للعمل.
     */
    public function restore($id)
    {
        $category = MarketingAssetCategory::withTrashed()->findOrFail($id);
        $category->restore();
        return $category;
    }

    /**
     * حذف نهائي للسجل من (Marketing Asset Category) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $category = MarketingAssetCategory::withTrashed()->findOrFail($id);
        $category->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
