<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketingSupportTypeController
 * الوحدة (Module): إدارة العملاء (CRM) (CRM)
 * المورد (Resource): Marketing Support Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Marketing Support Type" ضمن وحدة "إدارة العملاء (CRM)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\MarketingSupportType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class MarketingSupportTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Marketing Support Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = MarketingSupportType::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('code', 'like', "%$s%")
                    ->orWhere('name', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Marketing Support Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('marketing_support_type', 'store'));
        return response()->json(MarketingSupportType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Marketing Support Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(MarketingSupportType $marketingSupportType)
    {
        return $marketingSupportType->load(['marketingSupports']);
    }

    /**
     * تحديث بيانات سجل موجود من (Marketing Support Type) بناءً على المعرّف.
     */
    public function update(Request $request, MarketingSupportType $marketingSupportType)
    {
        $data = $request->validate(ValidationRules::for('marketing_support_type', 'update', $marketingSupportType));
        $marketingSupportType->update($data);
        return response()->json($marketingSupportType);
    }

    /**
     * حذف سجل من (Marketing Support Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(MarketingSupportType $marketingSupportType)
    {
        $marketingSupportType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Marketing Support Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = MarketingSupportType::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Marketing Support Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        MarketingSupportType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Marketing Support Type).
     */
    public function schema()
    {
        return ValidationRules::for('marketing_support_type', 'store');
    }
}
