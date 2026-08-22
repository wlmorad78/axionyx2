<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesmanSettlementController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Salesman Settlement
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salesman Settlement" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanSettlement;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanSettlementController extends Controller
{
    /**
     * عرض قائمة سجلات (Salesman Settlement) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanSettlement::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->sales_rep_id) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where('settlement_no', 'like', "%$s%");
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Salesman Settlement) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('salesman_settlement', 'store'));
        return response()->json(SalesmanSettlement::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salesman Settlement) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesmanSettlement $salesmanSettlement)
    {
        return $salesmanSettlement->load(['company', 'branch', 'salesRep', 'route', 'loadRequest', 'issueOrder']);
    }

    /**
     * تحديث بيانات سجل موجود من (Salesman Settlement) بناءً على المعرّف.
     */
    public function update(Request $request, SalesmanSettlement $salesmanSettlement)
    {
        $data = $request->validate(ValidationRules::for('salesman_settlement', 'update', $salesmanSettlement));
        $salesmanSettlement->update($data);
        return response()->json($salesmanSettlement);
    }

    /**
     * حذف سجل من (Salesman Settlement) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesmanSettlement $salesmanSettlement)
    {
        $salesmanSettlement->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Salesman Settlement) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesmanSettlement::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Salesman Settlement) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesmanSettlement::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salesman Settlement).
     */
    public function schema()
    {
        return ValidationRules::for('salesman_settlement', 'store');
    }
}
