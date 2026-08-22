<?php
/**
 * =====================================================================
 * متحكم (Controller): PaymentMethodController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Payment Method
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Payment Method" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    /**
     * عرض قائمة سجلات (Payment Method) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PaymentMethod::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Payment Method) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('payment_method', 'store'));
        $method = PaymentMethod::create($data);

        return response()->json($method, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Payment Method) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PaymentMethod $paymentMethod)
    {
        return $paymentMethod;
    }

    /**
     * تحديث بيانات سجل موجود من (Payment Method) بناءً على المعرّف.
     */
    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $data = $request->validate(ValidationRules::for('payment_method', 'update', $paymentMethod));
        $paymentMethod->update($data);

        return response()->json($paymentMethod);
    }

    /**
     * حذف سجل من (Payment Method) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Payment Method) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $method = PaymentMethod::onlyTrashed()->findOrFail($id);
        $method->restore();

        return response()->json($method);
    }

    /**
     * حذف نهائي للسجل من (Payment Method) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $method = PaymentMethod::onlyTrashed()->findOrFail($id);
        $method->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Payment Method).
     */
    public function schema()
    {
        return ValidationRules::for('payment_method', 'store');
    }
}
