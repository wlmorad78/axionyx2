<?php
/**
 * =====================================================================
 * متحكم (Controller): IssueOrderController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Issue Order
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Issue Order" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\IssueOrder;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IssueOrderController extends Controller
{
    /**
     * عرض قائمة سجلات (Issue Order) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = IssueOrder::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($request->warehouse_id) $query->where('warehouse_id', $request->warehouse_id);
        if ($request->load_request_id) $query->where('load_request_id', $request->load_request_id);
        if ($request->user_id) $query->where('user_id', $request->user_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('issue_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Issue Order) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('issue_order', 'store'));
        return response()->json(IssueOrder::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Issue Order) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(IssueOrder $issueOrder)
    {
        return $issueOrder->load([
            'company', 'branch', 'warehouse', 'loadRequest', 'employee',
            'salesTerritory', 'route', 'issuedByEmployee', 'receivedByEmployee',
            'approvedByEmployee', 'items.item', 'items.unit',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Issue Order) بناءً على المعرّف.
     */
    public function update(Request $request, IssueOrder $issueOrder)
    {
        $data = $request->validate(ValidationRules::for('issue_order', 'update', $issueOrder));
        $issueOrder->update($data);
        return response()->json($issueOrder);
    }

    /**
     * حذف سجل من (Issue Order) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(IssueOrder $issueOrder)
    {
        $issueOrder->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Issue Order) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = IssueOrder::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Issue Order) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        IssueOrder::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Issue Order).
     */
    public function schema()
    {
        return ValidationRules::for('issue_order', 'store');
    }
}
