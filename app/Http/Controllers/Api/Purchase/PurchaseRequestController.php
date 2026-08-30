<?php
/**
 * =====================================================================
 * متحكم (Controller): PurchaseRequestController
 * الوحدة (Module): المشتريات (Purchase)
 * المورد (Resource): Purchase Request
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Purchase Request" ضمن وحدة "المشتريات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Purchase;

use App\Http\Controllers\Controller;
use App\Models\PurchaseRequest;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class PurchaseRequestController extends Controller
{
    /**
     * عرض قائمة سجلات (Purchase Request) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = PurchaseRequest::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->priority) $query->where('priority', $request->priority);
        if ($request->requested_by) $query->where('requested_by', $request->requested_by);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('request_no', 'like', "%$s%")->orWhere('notes', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Purchase Request) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('purchase_request', 'store'));
        return response()->json(PurchaseRequest::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Purchase Request) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(PurchaseRequest $purchaseRequest)
    {
        return $purchaseRequest->load([
            'company', 'branch', 'requestedByEmployee', 'createdByEmployee', 'approvedByEmployee',
            'items.item', 'items.unit',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Purchase Request) بناءً على المعرّف.
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest)
    {
        $data = $request->validate(ValidationRules::for('purchase_request', 'update', $purchaseRequest));
        $purchaseRequest->update($data);
        return response()->json($purchaseRequest);
    }

    /**
     * حذف سجل من (Purchase Request) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(PurchaseRequest $purchaseRequest)
    {
        $purchaseRequest->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Purchase Request) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = PurchaseRequest::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Purchase Request) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        PurchaseRequest::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Purchase Request).
     */
    public function schema()
    {
        return ValidationRules::for('purchase_request', 'store');
    }
}
