<?php
/**
 * =====================================================================
 * متحكم (Controller): CurrencyController
 * الوحدة (Module): الإعدادات العامة (Settings)
 * المورد (Resource): Currency
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Currency" ضمن وحدة "الإعدادات العامة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Currency;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    /**
     * عرض قائمة سجلات (Currency) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = Currency::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Currency) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('currency', 'store'));
        $currency = Currency::create($data);

        return response()->json($currency, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Currency) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(Currency $currency)
    {
        return $currency;
    }

    /**
     * تحديث بيانات سجل موجود من (Currency) بناءً على المعرّف.
     */
    public function update(Request $request, Currency $currency)
    {
        $data = $request->validate(ValidationRules::for('currency', 'update', $currency));
        $currency->update($data);

        return response()->json($currency);
    }

    /**
     * حذف سجل من (Currency) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(Currency $currency)
    {
        $currency->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Currency) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);
        $currency->restore();

        return response()->json($currency);
    }

    /**
     * حذف نهائي للسجل من (Currency) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $currency = Currency::onlyTrashed()->findOrFail($id);
        $currency->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Currency).
     */
    public function schema()
    {
        return ValidationRules::for('currency', 'store');
    }
}
