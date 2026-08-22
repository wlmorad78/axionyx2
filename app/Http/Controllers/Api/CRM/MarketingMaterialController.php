<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingMaterialController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Material
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Material" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingMaterial;
use Illuminate\Http\Request;

class MarketingMaterialController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Material) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MarketingMaterial::with('unit');

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('material_code', 'like', "%{$s}%")
                    ->orWhere('material_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Material) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'material_code' => 'required|string|max:255',
            'material_name' => 'required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'cost' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $material = MarketingMaterial::create($data);
        return response()->json($material, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Material) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MarketingMaterial::with(['unit', 'customerMaterials'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Material) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $material = MarketingMaterial::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'material_code' => 'sometimes|required|string|max:255',
            'material_name' => 'sometimes|required|string|max:255',
            'unit_id' => 'nullable|exists:units,id',
            'cost' => 'numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $material->update($data);
        return $material;
    }

    /**
     * حذف سجل من (Marketing Material) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $material = MarketingMaterial::findOrFail($id);
        $material->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Material) وإعادته للعمل.
     */
    public function restore($id)
    {
        $material = MarketingMaterial::withTrashed()->findOrFail($id);
        $material->restore();
        return $material;
    }

    /**
     * حذف نهائي للسجل من (Marketing Material) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $material = MarketingMaterial::withTrashed()->findOrFail($id);
        $material->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
