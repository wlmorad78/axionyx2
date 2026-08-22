<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryClosingDetailController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Closing Detail
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Closing Detail" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryClosingDetail;
use Illuminate\Http\Request;

class TreasuryClosingDetailController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Closing Detail) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryClosingDetail::with(['closing']);

        if ($request->filled('treasury_daily_closing_id')) {
            $query->where('treasury_daily_closing_id', $request->treasury_daily_closing_id);
        }

        $details = $query->orderBy('id', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($details);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Closing Detail) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_daily_closing_id' => 'required|exists:treasury_daily_closings,id',
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ]);

        $detail = TreasuryClosingDetail::create($validated);

        return response()->json($detail->load('closing'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Closing Detail) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $detail = TreasuryClosingDetail::with(['closing'])->findOrFail($id);

        return response()->json($detail);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Closing Detail) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $detail = TreasuryClosingDetail::findOrFail($id);

        $validated = $request->validate([
            'treasury_daily_closing_id' => 'required|exists:treasury_daily_closings,id',
            'transaction_type' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ]);

        $detail->update($validated);

        return response()->json($detail->load('closing'));
    }

    /**
     * حذف سجل من (Treasury Closing Detail) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $detail = TreasuryClosingDetail::findOrFail($id);
        $detail->delete();

        return response()->json(['message' => 'Treasury closing detail deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Closing Detail) وإعادته للعمل.
     */
    public function restore($id)
    {
        $detail = TreasuryClosingDetail::onlyTrashed()->findOrFail($id);
        $detail->restore();

        return response()->json($detail->load('closing'));
    }

    /**
     * حذف نهائي للسجل من (Treasury Closing Detail) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $detail = TreasuryClosingDetail::onlyTrashed()->findOrFail($id);
        $detail->forceDelete();

        return response()->json(['message' => 'Treasury closing detail permanently deleted']);
    }
}
