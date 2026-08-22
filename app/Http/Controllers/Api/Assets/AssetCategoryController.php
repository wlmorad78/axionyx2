<?php
/**
 * =====================================================================
 * متحكم (Controller): AssetCategoryController
 * الوحدة (Module): الأصول الثابتة (Assets)
 * المورد (Resource): Asset Category
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Asset Category" ضمن وحدة "الأصول الثابتة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\Assets\AssetCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    /**
     * عرض قائمة سجلات (Asset Category) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AssetCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Asset Category) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_category', 'create'));
        $assetCategory = AssetCategory::create($data);
        return response()->json($assetCategory, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Asset Category) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return AssetCategory::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Asset Category) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $assetCategory = AssetCategory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_category', 'update', $assetCategory));
        $assetCategory->update($data);
        return $assetCategory;
    }

    /**
     * حذف سجل من (Asset Category) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $assetCategory = AssetCategory::findOrFail($id);
        $assetCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Asset Category) وإعادته للعمل.
     */
    public function restore($id)
    {
        $assetCategory = AssetCategory::withTrashed()->findOrFail($id);
        $assetCategory->restore();
        return $assetCategory;
    }

    /**
     * حذف نهائي للسجل من (Asset Category) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $assetCategory = AssetCategory::withTrashed()->findOrFail($id);
        $assetCategory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
