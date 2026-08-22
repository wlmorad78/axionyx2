<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerMarketingMaterialController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Marketing Material
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Marketing Material" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerMarketingMaterial;
use Illuminate\Http\Request;

class CustomerMarketingMaterialController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Marketing Material) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CustomerMarketingMaterial::with(['customer', 'marketingMaterial']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('distribution_date', 'like', "%{$s}%");
            });
        }

        if ($request->filled('customer_id')) $query->where('customer_id', $request->customer_id);
        if ($request->filled('marketing_material_id')) $query->where('marketing_material_id', $request->marketing_material_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Marketing Material) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'marketing_material_id' => 'required|exists:marketing_materials,id',
            'distribution_date' => 'required|date',
            'qty' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $record = CustomerMarketingMaterial::create($data);
        return response()->json($record, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Marketing Material) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return CustomerMarketingMaterial::with(['customer', 'marketingMaterial'])->findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Marketing Material) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $record = CustomerMarketingMaterial::findOrFail($id);

        $data = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'marketing_material_id' => 'sometimes|required|exists:marketing_materials,id',
            'distribution_date' => 'sometimes|required|date',
            'qty' => 'numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $record->update($data);
        return $record;
    }

    /**
     * حذف سجل من (Customer Marketing Material) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $record = CustomerMarketingMaterial::findOrFail($id);
        $record->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Marketing Material) وإعادته للعمل.
     */
    public function restore($id)
    {
        $record = CustomerMarketingMaterial::withTrashed()->findOrFail($id);
        $record->restore();
        return $record;
    }

    /**
     * حذف نهائي للسجل من (Customer Marketing Material) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $record = CustomerMarketingMaterial::withTrashed()->findOrFail($id);
        $record->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
