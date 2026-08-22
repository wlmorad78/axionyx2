<?php
/**
 * =====================================================================
 * متحكم (Controller): ItemTaxProfileController
 * الوحدة (Module): الضرائب والفواتير الإلكترونية (Tax)
 * المورد (Resource): Item Tax Profile
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Item Tax Profile" ضمن وحدة "الضرائب والفواتير الإلكترونية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Tax;

use App\Http\Controllers\Controller;
use App\Models\Tax\ItemTaxProfile;
use Illuminate\Http\Request;

class ItemTaxProfileController extends Controller
{
    /**
     * عرض قائمة سجلات (Item Tax Profile) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ItemTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('item_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $itemTaxProfiles = $query->paginate($perPage);

        return response()->json($itemTaxProfiles);
    }

    /**
     * إنشاء سجل جديد لـ (Item Tax Profile) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $itemTaxProfile = ItemTaxProfile::create($validated);

        return response()->json($itemTaxProfile, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Item Tax Profile) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ItemTaxProfile $itemTaxProfile)
    {
        return response()->json($itemTaxProfile);
    }

    /**
     * تحديث بيانات سجل موجود من (Item Tax Profile) بناءً على المعرّف.
     */
    public function update(Request $request, ItemTaxProfile $itemTaxProfile)
    {
        $validated = $request->validate([
            'item_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $itemTaxProfile->update($validated);

        return response()->json($itemTaxProfile);
    }

    /**
     * حذف سجل من (Item Tax Profile) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ItemTaxProfile $itemTaxProfile)
    {
        $itemTaxProfile->delete();

        return response()->json(null, 204);
    }
}
