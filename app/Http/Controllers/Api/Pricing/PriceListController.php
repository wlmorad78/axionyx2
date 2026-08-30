<?php
/**
 * =====================================================================
 * متحكم (Controller): PriceListController
 * الوحدة (Module): التسعير والأسعار (Pricing)
 * المورد (Resource): Price List
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Price List" ضمن وحدة "التسعير والأسعار".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\PriceList;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PriceListController extends Controller
{
    /**
     * عرض قائمة سجلات (Price List) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = PriceList::with($with);

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
     * إنشاء سجل جديد لـ (Price List) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('price_list', 'store'));

        return response()->json(PriceList::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Price List) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PriceList $price_list)
    {
        return $price_list->load(['company', 'itemPrices']);
    }

    /**
     * تحديث بيانات سجل موجود من (Price List) بناءً على المعرّف.
     */
    public function update(Request $request, PriceList $price_list)
    {
        $data = $request->validate(ValidationRules::for('price_list', 'update', $price_list));

        $price_list->update($data);

        return response()->json($price_list);
    }

    /**
     * حذف سجل من (Price List) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PriceList $price_list)
    {
        if ($price_list->is_default) {
            return response()->json(['message' => 'Ù„Ø§ ÙŠÙ…ÙƒÙ† Ø­Ø°Ù Ø§Ù„Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø§ÙØªØ±Ø§Ø¶ÙŠØ©'], 403);
        }

        $price_list->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Price List) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = PriceList::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Price List) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PriceList::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Price List).
     */
    public function schema()
    {
        return ValidationRules::for('price_list', 'store');
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Price List).
     */
    public function nextCode(Request $request)
    {
        $query = PriceList::withTrashed()
            ->where('code', 'like', 'PL-%');

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        $last = $query->get()
            ->filter(fn($item) => preg_match('/^PL-\d{5}$/', $item->code))
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($last ?? 0) + 1;

        return response()->json(['code' => 'PL-' . str_pad($next, 5, '0', STR_PAD_LEFT)]);
    }
}
