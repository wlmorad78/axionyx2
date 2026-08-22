<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryCashLimitController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Cash Limit
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Cash Limit" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryCashLimit;
use Illuminate\Http\Request;

class TreasuryCashLimitController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Cash Limit) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryCashLimit::with(['treasury']);

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        $limits = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($limits);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Cash Limit) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'minimum_limit' => 'required|numeric',
            'maximum_limit' => 'required|numeric',
            'alert_limit' => 'required|numeric',
        ]);

        $limit = TreasuryCashLimit::create($validated);

        return response()->json($limit->load('treasury'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Cash Limit) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $limit = TreasuryCashLimit::with(['treasury'])->findOrFail($id);

        return response()->json($limit);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Cash Limit) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $limit = TreasuryCashLimit::findOrFail($id);

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'minimum_limit' => 'required|numeric',
            'maximum_limit' => 'required|numeric',
            'alert_limit' => 'required|numeric',
        ]);

        $limit->update($validated);

        return response()->json($limit->load('treasury'));
    }

    /**
     * حذف سجل من (Treasury Cash Limit) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $limit = TreasuryCashLimit::findOrFail($id);
        $limit->delete();

        return response()->json(['message' => 'Treasury cash limit deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Cash Limit) وإعادته للعمل.
     */
    public function restore($id)
    {
        $limit = TreasuryCashLimit::onlyTrashed()->findOrFail($id);
        $limit->restore();

        return response()->json($limit->load('treasury'));
    }

    /**
     * حذف نهائي للسجل من (Treasury Cash Limit) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $limit = TreasuryCashLimit::onlyTrashed()->findOrFail($id);
        $limit->forceDelete();

        return response()->json(['message' => 'Treasury cash limit permanently deleted']);
    }
}
