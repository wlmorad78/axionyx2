<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerTaxProfileController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Tax Profile
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Tax Profile" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\Tax\CustomerTaxProfile;
use Illuminate\Http\Request;

class CustomerTaxProfileController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Tax Profile) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = CustomerTaxProfile::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('customer_id', 'like', "%{$search}%");
        }

        $perPage = $request->input('per_page', 15);
        $customerTaxProfiles = $query->paginate($perPage);

        return response()->json($customerTaxProfiles);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Tax Profile) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required',
            'is_taxable' => 'boolean',
        ]);

        $customerTaxProfile = CustomerTaxProfile::create($validated);

        return response()->json($customerTaxProfile, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Tax Profile) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerTaxProfile $customerTaxProfile)
    {
        return response()->json($customerTaxProfile);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Tax Profile) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerTaxProfile $customerTaxProfile)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required',
            'is_taxable' => 'sometimes|boolean',
        ]);

        $customerTaxProfile->update($validated);

        return response()->json($customerTaxProfile);
    }

    /**
     * حذف سجل من (Customer Tax Profile) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerTaxProfile $customerTaxProfile)
    {
        $customerTaxProfile->delete();

        return response()->json(null, 204);
    }
}
