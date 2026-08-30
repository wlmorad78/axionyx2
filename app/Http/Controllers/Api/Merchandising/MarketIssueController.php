<?php
/**
 * =====================================================================
 * متحكم (Controller): MarketIssueController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Market Issue
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Market Issue" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MarketIssue;
use Illuminate\Http\Request;

class MarketIssueController extends Controller
{
    /**
     * عرض قائمة سجلات (Market Issue) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = MarketIssue::with($with);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('issue_type', 'like', "%$s%")
                    ->orWhere('priority', 'like', "%$s%")
                    ->orWhere('status', 'like', "%$s%");
            });
        }

        if ($request->trashed) {
            $query->onlyTrashed();
        }

        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Market Issue) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'required|exists:users,id',
            'issue_date' => 'required|date',
            'issue_type' => 'required|in:PRICE,PROMOTION,QUALITY,AVAILABILITY,COMPETITOR_ACTIVITY,OTHER',
            'description' => 'required|string',
            'priority' => 'required|in:LOW,NORMAL,HIGH,URGENT',
            'status' => 'required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        return response()->json(MarketIssue::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Market Issue) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(MarketIssue $marketIssue)
    {
        return $marketIssue->load(['customer', 'salesRep']);
    }

    /**
     * تحديث بيانات سجل موجود من (Market Issue) بناءً على المعرّف.
     */
    public function update(Request $request, MarketIssue $marketIssue)
    {
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'sales_rep_id' => 'sometimes|required|exists:users,id',
            'issue_date' => 'sometimes|required|date',
            'issue_type' => 'sometimes|required|in:PRICE,PROMOTION,QUALITY,AVAILABILITY,COMPETITOR_ACTIVITY,OTHER',
            'description' => 'sometimes|required|string',
            'priority' => 'sometimes|required|in:LOW,NORMAL,HIGH,URGENT',
            'status' => 'sometimes|required|in:OPEN,IN_PROGRESS,RESOLVED,CLOSED',
        ]);

        $marketIssue->update($data);
        return response()->json($marketIssue);
    }

    /**
     * حذف سجل من (Market Issue) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(MarketIssue $marketIssue)
    {
        $marketIssue->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Market Issue) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $model = MarketIssue::onlyTrashed()->findOrFail($id);
        $model->restore();
        return response()->json($model);
    }

    /**
     * حذف نهائي للسجل من (Market Issue) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        MarketIssue::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }
}
