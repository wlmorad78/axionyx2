<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesmanDebtPaymentLineController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Salesman Debt Payment Line
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salesman Debt Payment Line" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesmanDebtPaymentLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanDebtPaymentLineController extends Controller
{
    /**
     * عرض قائمة سجلات (Salesman Debt Payment Line) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanDebtPaymentLine::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->salesman_debt_id) {
            $query->where('salesman_debt_id', $request->salesman_debt_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->from_date) {
            $query->whereDate('payment_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('payment_date', '<=', $request->to_date);
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->orderByDesc('payment_date')->paginate($request->per_page ?? 15);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salesman Debt Payment Line) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesmanDebtPaymentLine $salesmanDebtPaymentLine)
    {
        return $salesmanDebtPaymentLine->load([
            'salesmanDebt', 'salesmanAccount', 'salesman',
            'paymentMethod', 'treasury', 'collection',
            'receivedByEmployee', 'createdByEmployee',
        ]);
    }

    /**
     * حذف سجل من (Salesman Debt Payment Line) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesmanDebtPaymentLine $salesmanDebtPaymentLine)
    {
        $salesmanDebtPaymentLine->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Salesman Debt Payment Line) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = SalesmanDebtPaymentLine::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Salesman Debt Payment Line) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        SalesmanDebtPaymentLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salesman Debt Payment Line).
     */
    public function schema()
    {
        return ValidationRules::for('salesman_debt_payment_line', 'store');
    }
}