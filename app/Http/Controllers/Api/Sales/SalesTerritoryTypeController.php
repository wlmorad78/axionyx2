<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesTerritoryTypeController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Sales Territory Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Sales Territory Type" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesTerritoryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesTerritoryTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Sales Territory Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesTerritoryType::with($with);
        if ($request->trashed) {
            $query->onlyTrashed();
        }
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Sales Territory Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('sales_territory_type', 'store'));
        $type = SalesTerritoryType::create($data);
        return response()->json($type, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Sales Territory Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesTerritoryType $salesTerritoryType)
    {
        return $salesTerritoryType;
    }

    /**
     * تحديث بيانات سجل موجود من (Sales Territory Type) بناءً على المعرّف.
     */
    public function update(Request $request, SalesTerritoryType $salesTerritoryType)
    {
        $data = $request->validate(ValidationRules::for('sales_territory_type', 'update', $salesTerritoryType));
        $salesTerritoryType->update($data);
        return response()->json($salesTerritoryType);
    }

    /**
     * حذف سجل من (Sales Territory Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesTerritoryType $salesTerritoryType)
    {
        if ($salesTerritoryType->is_system) {
            return response()->json(['message' => 'لا يمكن حذف نوع منطقة مبيعات نظام'], 403);
        }
        $salesTerritoryType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Sales Territory Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $type = SalesTerritoryType::onlyTrashed()->findOrFail($id);
        $type->restore();
        return response()->json($type);
    }

    /**
     * حذف نهائي للسجل من (Sales Territory Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        $type = SalesTerritoryType::onlyTrashed()->findOrFail($id);
        $type->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Sales Territory Type).
     */
    public function schema()
    {
        return ValidationRules::for('sales_territory_type', 'store');
    }
}
