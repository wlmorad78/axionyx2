<?php
/**
 * =====================================================================
 * متحكم (Controller): SupplierTaxProfileController
 * الوحدة (Module): الموردين (Suppliers)
 * المورد (Resource): Supplier Tax Profile
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Supplier Tax Profile" ضمن وحدة "الموردين".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\SupplierTaxProfile;
use Illuminate\Http\Request;

class SupplierTaxProfileController extends Controller
{
    /**
     * عرض قائمة سجلات (Supplier Tax Profile) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = SupplierTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('supplier_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $supplierTaxProfiles = $query->paginate($perPage);

        return response()->json($supplierTaxProfiles);
    }

    /**
     * إنشاء سجل جديد لـ (Supplier Tax Profile) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $supplierTaxProfile = SupplierTaxProfile::create($validated);

        return response()->json($supplierTaxProfile, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Supplier Tax Profile) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SupplierTaxProfile $supplierTaxProfile)
    {
        return response()->json($supplierTaxProfile);
    }

    /**
     * تحديث بيانات سجل موجود من (Supplier Tax Profile) بناءً على المعرّف.
     */
    public function update(Request $request, SupplierTaxProfile $supplierTaxProfile)
    {
        $validated = $request->validate([
            'supplier_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $supplierTaxProfile->update($validated);

        return response()->json($supplierTaxProfile);
    }

    /**
     * حذف سجل من (Supplier Tax Profile) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SupplierTaxProfile $supplierTaxProfile)
    {
        $supplierTaxProfile->delete();

        return response()->json(null, 204);
    }
}
