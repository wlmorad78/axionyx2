<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleSettlementController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Settlement
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Settlement" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleSettlement;
use Illuminate\Http\Request;

class VehicleSettlementController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Settlement) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleSettlement::with(['items', 'vehicle', 'salesRep']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('settlement_date_from')) {
            $query->where('settlement_date', '>=', $request->settlement_date_from);
        }

        if ($request->filled('settlement_date_to')) {
            $query->where('settlement_date', '<=', $request->settlement_date_to);
        }

        $settlements = $query->paginate($request->get('per_page', 15));

        return response()->json($settlements);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Settlement) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'sales_rep_id' => 'required',
            'settlement_no' => 'required|unique:vehicle_settlements,settlement_no',
            'settlement_date' => 'required|date',
            'opening_stock_value' => 'nullable|numeric',
            'loaded_value' => 'nullable|numeric',
            'sales_value' => 'nullable|numeric',
            'collection_value' => 'nullable|numeric',
            'return_value' => 'nullable|numeric',
            'expense_value' => 'nullable|numeric',
            'closing_stock_value' => 'nullable|numeric',
            'cash_difference' => 'nullable|numeric',
            'stock_difference' => 'nullable|numeric',
            'status' => 'required|in:DRAFT,COMPLETED,APPROVED',
        ]);

        $settlement = VehicleSettlement::create($validated);

        return response()->json($settlement->load(['items', 'vehicle', 'salesRep']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Settlement) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $settlement = VehicleSettlement::with(['items', 'vehicle', 'salesRep'])->findOrFail($id);

        return response()->json($settlement);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Settlement) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $settlement = VehicleSettlement::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'sales_rep_id' => 'required',
            'settlement_no' => 'required|unique:vehicle_settlements,settlement_no,' . $id,
            'settlement_date' => 'required|date',
            'opening_stock_value' => 'nullable|numeric',
            'loaded_value' => 'nullable|numeric',
            'sales_value' => 'nullable|numeric',
            'collection_value' => 'nullable|numeric',
            'return_value' => 'nullable|numeric',
            'expense_value' => 'nullable|numeric',
            'closing_stock_value' => 'nullable|numeric',
            'cash_difference' => 'nullable|numeric',
            'stock_difference' => 'nullable|numeric',
            'status' => 'required|in:DRAFT,COMPLETED,APPROVED',
        ]);

        $settlement->update($validated);

        return response()->json($settlement->load(['items', 'vehicle', 'salesRep']));
    }

    /**
     * حذف سجل من (Vehicle Settlement) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $settlement = VehicleSettlement::findOrFail($id);
        $settlement->delete();

        return response()->json(['message' => 'Vehicle settlement deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Settlement) وإعادته للعمل.
     */
    public function restore($id)
    {
        $settlement = VehicleSettlement::withTrashed()->findOrFail($id);
        $settlement->restore();

        return response()->json($settlement->load(['items', 'vehicle', 'salesRep']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Settlement) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $settlement = VehicleSettlement::withTrashed()->findOrFail($id);
        $settlement->forceDelete();

        return response()->json(['message' => 'Vehicle settlement permanently deleted']);
    }
}
