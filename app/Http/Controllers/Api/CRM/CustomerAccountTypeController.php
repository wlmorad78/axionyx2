<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerAccountTypeController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Account Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Account Type" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccountType;
use Illuminate\Http\Request;

class CustomerAccountTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Account Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerAccountType::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Customer Account Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'code' => 'required|string|max:50|unique:customer_account_types,code,null,id,deleted_at,NULL',
            'name_ar' => 'required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        return response()->json(CustomerAccountType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Account Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerAccountType $customerAccountType)
    {
        return $customerAccountType->load(['company']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Account Type) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerAccountType $customerAccountType)
    {
        $data = $request->validate([
            'code' => 'sometimes|required|string|max:50|unique:customer_account_types,code,' . $customerAccountType->id . ',deleted_at,NULL',
            'name_ar' => 'sometimes|required|string|max:255',
            'name_en' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);
        $customerAccountType->update($data);
        return response()->json($customerAccountType);
    }

    /**
     * حذف سجل من (Customer Account Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerAccountType $customerAccountType)
    {
        $customerAccountType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Account Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerAccountType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Account Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerAccountType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Customer Account Type).
     */
    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = CustomerAccountType::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'AT-%')->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        if ($last && preg_match('/^AT-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['next_code' => 'AT-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
    }
}
