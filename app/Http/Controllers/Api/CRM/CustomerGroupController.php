<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerGroupController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Group
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Group" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerGroup;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerGroupController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Group) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerGroup::with($with);

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
     * إنشاء سجل جديد لـ (Customer Group) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_group', 'store'));
        return response()->json(CustomerGroup::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Group) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerGroup $customerGroup)
    {
        return $customerGroup->load(['company']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Group) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerGroup $customerGroup)
    {
        $data = $request->validate(ValidationRules::for('customer_group', 'update', $customerGroup));
        $customerGroup->update($data);
        return response()->json($customerGroup);
    }

    /**
     * حذف سجل من (Customer Group) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerGroup $customerGroup)
    {
        $customerGroup->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Group) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerGroup::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Group) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerGroup::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Group).
     */
    public function schema()
    {
        return ValidationRules::for('customer_group', 'store');
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Customer Group).
     */
    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = CustomerGroup::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'CG-%')->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        if ($last && preg_match('/^CG-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['next_code' => 'CG-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
    }
}
