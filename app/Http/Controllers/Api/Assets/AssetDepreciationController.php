<?php
/**
 * =====================================================================
 * متحكم (Controller): AssetDepreciationController
 * الوحدة (Module): الأصول الثابتة (Assets)
 * المورد (Resource): Asset Depreciation
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Asset Depreciation" ضمن وحدة "الأصول الثابتة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\AssetDepreciation;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetDepreciationController extends Controller
{
    /**
     * عرض قائمة سجلات (Asset Depreciation) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AssetDepreciation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('id', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Asset Depreciation) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_depreciation', 'create'));
        $assetDepreciation = AssetDepreciation::create($data);
        return response()->json($assetDepreciation, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Asset Depreciation) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return AssetDepreciation::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Asset Depreciation) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $assetDepreciation = AssetDepreciation::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_depreciation', 'update', $assetDepreciation));
        $assetDepreciation->update($data);
        return $assetDepreciation;
    }

    /**
     * حذف سجل من (Asset Depreciation) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $assetDepreciation = AssetDepreciation::findOrFail($id);
        $assetDepreciation->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Asset Depreciation) وإعادته للعمل.
     */
    public function restore($id)
    {
        $assetDepreciation = AssetDepreciation::withTrashed()->findOrFail($id);
        $assetDepreciation->restore();
        return $assetDepreciation;
    }

    /**
     * حذف نهائي للسجل من (Asset Depreciation) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $assetDepreciation = AssetDepreciation::withTrashed()->findOrFail($id);
        $assetDepreciation->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
