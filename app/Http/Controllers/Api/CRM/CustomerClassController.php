<?php
/**
 * =====================================================================
 * متحكم (Controller): CustomerClassController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Customer Class
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Customer Class" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CustomerClass;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CustomerClassController extends Controller
{
    /**
     * عرض قائمة سجلات (Customer Class) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = CustomerClass::with($with);

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
     * إنشاء سجل جديد لـ (Customer Class) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('customer_class', 'store'));
        return response()->json(CustomerClass::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Customer Class) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(CustomerClass $customerClass)
    {
        return $customerClass->load(['company']);
    }

    /**
     * تحديث بيانات سجل موجود من (Customer Class) بناءً على المعرّف.
     */
    public function update(Request $request, CustomerClass $customerClass)
    {
        $data = $request->validate(ValidationRules::for('customer_class', 'update', $customerClass));
        $customerClass->update($data);
        return response()->json($customerClass);
    }

    /**
     * حذف سجل من (Customer Class) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(CustomerClass $customerClass)
    {
        $customerClass->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Customer Class) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = CustomerClass::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Customer Class) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        CustomerClass::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Customer Class).
     */
    public function schema()
    {
        return ValidationRules::for('customer_class', 'store');
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Customer Class).
     */
    public function nextCode(Request $request)
    {
        $companyId = $request->company_id;
        $query = CustomerClass::query()->withTrashed();
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        $last = $query->where('code', 'like', 'CC-%')->orderByRaw("CAST(SUBSTRING(code, 4) AS UNSIGNED) DESC")->first();
        if ($last && preg_match('/^CC-(\d+)$/', $last->code, $m)) {
            $next = (int) $m[1] + 1;
        } else {
            $next = 1;
        }
        return response()->json(['next_code' => 'CC-' . str_pad((string) $next, 3, '0', STR_PAD_LEFT)]);
    }
}
