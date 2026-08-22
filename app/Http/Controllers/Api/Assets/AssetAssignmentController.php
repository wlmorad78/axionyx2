<?php
/**
 * =====================================================================
 * متحكم (Controller): AssetAssignmentController
 * الوحدة (Module): الأصول الثابتة (Assets)
 * المورد (Resource): Asset Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Asset Assignment" ضمن وحدة "الأصول الثابتة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Assets;

use App\Http\Controllers\Controller;
use App\Models\AssetAssignment;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class AssetAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Asset Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = AssetAssignment::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Asset Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('asset_assignment', 'create'));
        $assetAssignment = AssetAssignment::create($data);
        return response()->json($assetAssignment, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Asset Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return AssetAssignment::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Asset Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $assetAssignment = AssetAssignment::findOrFail($id);
        $data = $request->validate(ValidationRules::for('asset_assignment', 'update', $assetAssignment));
        $assetAssignment->update($data);
        return $assetAssignment;
    }

    /**
     * حذف سجل من (Asset Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $assetAssignment = AssetAssignment::findOrFail($id);
        $assetAssignment->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Asset Assignment) وإعادته للعمل.
     */
    public function restore($id)
    {
        $assetAssignment = AssetAssignment::withTrashed()->findOrFail($id);
        $assetAssignment->restore();
        return $assetAssignment;
    }

    /**
     * حذف نهائي للسجل من (Asset Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $assetAssignment = AssetAssignment::withTrashed()->findOrFail($id);
        $assetAssignment->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
