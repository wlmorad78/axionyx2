<?php
/**
 * =====================================================================
 * متحكم (Controller): UnitController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Unit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Unit" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * عرض قائمة سجلات (Unit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];

        $query = Unit::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")
                    ->orWhere('code', 'like', "%$s%")
                    ->orWhere('symbol', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Unit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('unit', 'store'));

        return response()->json(Unit::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Unit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $model = Unit::withoutTrashed()->findOrFail($id);
        return response()->json($model);
    }

    /**
     * تحديث بيانات سجل موجود من (Unit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $model = Unit::withoutTrashed()->findOrFail($id);
        $data = $request->validate(ValidationRules::for('unit', 'update', $model));

        $model->update($data);
        $model->refresh();

        return response()->json($model);
    }

    /**
     * حذف سجل من (Unit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $model = Unit::withoutTrashed()->findOrFail($id);
        $model->delete();

        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Unit) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = Unit::onlyTrashed()->findOrFail($id);
        $model->restore();

        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Unit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        Unit::onlyTrashed()->findOrFail($id)->forceDelete();

        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Unit).
     */
    public function schema()
    {
        return ValidationRules::for('unit', 'store');
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Unit).
     */
    public function nextCode(Request $request)
    {
        $query = Unit::withTrashed()
            ->where('code', 'like', 'UNT-%');

        $last = $query->get()
            ->filter(fn($item) => preg_match('/^UNT-\d{3}$/', $item->code))
            ->map(fn($item) => (int) preg_replace('/\D/', '', $item->code))
            ->filter(fn($num) => $num > 0)
            ->max();

        $next = ($last ?? 0) + 1;

        return response()->json(['code' => 'UNT-' . str_pad($next, 3, '0', STR_PAD_LEFT)]);
    }
}
