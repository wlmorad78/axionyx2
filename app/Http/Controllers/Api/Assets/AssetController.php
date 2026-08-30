<?php
/**
 * =====================================================================
 * متحكم (Controller): AssetController
 * الوحدة (Module): الأصول الثابتة (Assets)
 * المورد (Resource): Asset
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Asset" ضمن وحدة "الأصول الثابتة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    /**
     * عرض قائمة سجلات (Asset) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Asset::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('asset_code', 'like', "%{$s}%")
                    ->orWhere('asset_name', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Asset) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset', 'create'));
        $asset = Asset::create($data);
        return response()->json($asset, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Asset) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Asset::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Asset) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $asset = Asset::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset', 'update', $asset));
        $asset->update($data);
        return $asset;
    }

    /**
     * حذف سجل من (Asset) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $asset = Asset::findOrFail($id);
        $asset->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Asset) وإعادته للعمل.
     */
    public function restore($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);
        $asset->restore();
        return $asset;
    }

    /**
     * حذف نهائي للسجل من (Asset) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $asset = Asset::withTrashed()->findOrFail($id);
        $asset->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
