<?php
/**
 * =====================================================================
 * متحكم (Controller): TreasuryCustodyController
 * الوحدة (Module): الخزينة والنقد (Treasury)
 * المورد (Resource): Treasury Custody
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Treasury Custody" ضمن وحدة "الخزينة والنقد".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Treasury;

use App\Http\Controllers\Controller;
use App\Models\Treasury\TreasuryCustody;
use Illuminate\Http\Request;

class TreasuryCustodyController extends Controller
{
    /**
     * عرض قائمة سجلات (Treasury Custody) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = TreasuryCustody::with(['employee', 'treasury', 'transactions']);

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('treasury_id')) {
            $query->where('treasury_id', $request->treasury_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $custodies = $query->orderBy('issue_date', 'desc')->paginate($request->get('per_page', 15));

        return response()->json($custodies);
    }

    /**
     * إنشاء سجل جديد لـ (Treasury Custody) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'custody_no' => 'required|unique:treasury_custodies,custody_no',
            'issue_date' => 'required|date',
            'amount' => 'required|numeric',
            'status' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $custody = TreasuryCustody::create($validated);

        return response()->json($custody->load(['employee', 'treasury', 'transactions']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Treasury Custody) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $custody = TreasuryCustody::with(['employee', 'treasury', 'transactions'])->findOrFail($id);

        return response()->json($custody);
    }

    /**
     * تحديث بيانات سجل موجود من (Treasury Custody) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $custody = TreasuryCustody::findOrFail($id);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'treasury_id' => 'required|exists:treasuries,id',
            'custody_no' => 'required|unique:treasury_custodies,custody_no,' . $id,
            'issue_date' => 'required|date',
            'amount' => 'required|numeric',
            'status' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $custody->update($validated);

        return response()->json($custody->load(['employee', 'treasury', 'transactions']));
    }

    /**
     * حذف سجل من (Treasury Custody) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $custody = TreasuryCustody::findOrFail($id);
        $custody->delete();

        return response()->json(['message' => 'Treasury custody deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Treasury Custody) وإعادته للعمل.
     */
    public function restore($id)
    {
        $custody = TreasuryCustody::onlyTrashed()->findOrFail($id);
        $custody->restore();

        return response()->json($custody->load(['employee', 'treasury', 'transactions']));
    }

    /**
     * حذف نهائي للسجل من (Treasury Custody) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $custody = TreasuryCustody::onlyTrashed()->findOrFail($id);
        $custody->forceDelete();

        return response()->json(['message' => 'Treasury custody permanently deleted']);
    }
}
