<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryAlertController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Alert
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Alert" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\TreasuryAlert;
use Illuminate\Http\Request;

class TreasuryAlertController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Alert) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryAlert::with(['treasury']);

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        if ($request->filled('alert_type')) {
            $query->where('alert_type', $request->alert_type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $alerts = $query->orderBy('alert_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($alerts);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Alert) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'alert_type' => 'required|in:LOW_CASH,HIGH_CASH,SHORTAGE,OVERAGE',
            'alert_date' => 'required|date',
            'message' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $alert = TreasuryAlert::create($validated);

        return response()->json($alert->load('treasury'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Alert) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $alert = TreasuryAlert::with(['treasury'])->findOrFail($id);

        return response()->json($alert);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Alert) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $alert = TreasuryAlert::findOrFail($id);

        $validated = $request->validate([
            'treasury_id' => 'required|exists:treasuries,id',
            'alert_type' => 'required|in:LOW_CASH,HIGH_CASH,SHORTAGE,OVERAGE',
            'alert_date' => 'required|date',
            'message' => 'required|string',
            'status' => 'required|string|max:255',
        ]);

        $alert->update($validated);

        return response()->json($alert->load('treasury'));
    }

    /**
     * حذف سجل من (Treasury Alert) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $alert = TreasuryAlert::findOrFail($id);
        $alert->delete();

        return response()->json(['message' => 'Treasury alert deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Alert) وإعادته للعمل.
     */
    public function restore($id)
    {
        $alert = TreasuryAlert::onlyTrashed()->findOrFail($id);
        $alert->restore();

        return response()->json($alert->load('treasury'));
    }

    /**
     * حذف نهائي للسجل من (Treasury Alert) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $alert = TreasuryAlert::onlyTrashed()->findOrFail($id);
        $alert->forceDelete();

        return response()->json(['message' => 'Treasury alert permanently deleted']);
    }
}
