<?php
/**
 * =====================================================================
 * متحكم (Controller): SalesmanAccountMovementController
 * الوحدة (Module): المبيعات (Sales)
 * المورد (Resource): Salesman Account Movement
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Salesman Account Movement" ضمن وحدة "المبيعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\Sales\SalesmanAccountMovement;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class SalesmanAccountMovementController extends Controller
{
    /**
     * عرض قائمة سجلات (Salesman Account Movement) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = SalesmanAccountMovement::with($with);

        if ($request->company_id) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->salesman_id) {
            $query->where('salesman_id', $request->salesman_id);
        }

        if ($request->salesman_account_id) {
            $query->where('salesman_account_id', $request->salesman_account_id);
        }

        if ($request->movement_type) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->from_date) {
            $query->whereDate('movement_date', '>=', $request->from_date);
        }

        if ($request->to_date) {
            $query->whereDate('movement_date', '<=', $request->to_date);
        }

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('document_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }

        return $query->orderByDesc('movement_date')->paginate($request->per_page ?? 15);
    }

    /**
     * عرض تفاصيل سجل محدد من (Salesman Account Movement) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(SalesmanAccountMovement $salesmanAccountMovement)
    {
        return $salesmanAccountMovement->load(['salesmanAccount', 'salesman', 'createdByEmployee']);
    }

    /**
     * حذف سجل من (Salesman Account Movement) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(SalesmanAccountMovement $salesmanAccountMovement)
    {
        $salesmanAccountMovement->delete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Salesman Account Movement).
     */
    public function schema()
    {
        return ValidationRules::for('salesman_account_movement', 'store');
    }
}