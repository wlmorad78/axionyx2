<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleStockCountController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Stock Count
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Stock Count" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleStockCount;
use Illuminate\Http\Request;

class VehicleStockCountController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Stock Count) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleStockCount::with(['items']);

        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $counts = $query->paginate($request->get('per_page', 15));

        return response()->json($counts);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Stock Count) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required',
            'count_no' => 'required|unique:vehicle_stock_counts,count_no',
            'count_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $count = VehicleStockCount::create($validated);

        return response()->json($count->load('items'), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Stock Count) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $count = VehicleStockCount::with(['items'])->findOrFail($id);

        return response()->json($count);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Stock Count) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $count = VehicleStockCount::findOrFail($id);

        $validated = $request->validate([
            'vehicle_id' => 'required',
            'count_no' => 'required|unique:vehicle_stock_counts,count_no,' . $id,
            'count_date' => 'required|date',
            'status' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $count->update($validated);

        return response()->json($count->load('items'));
    }

    /**
     * حذف سجل من (Vehicle Stock Count) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $count = VehicleStockCount::findOrFail($id);
        $count->delete();

        return response()->json(['message' => 'Vehicle stock count deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Stock Count) وإعادته للعمل.
     */
    public function restore($id)
    {
        $count = VehicleStockCount::withTrashed()->findOrFail($id);
        $count->restore();

        return response()->json($count->load('items'));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Stock Count) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $count = VehicleStockCount::withTrashed()->findOrFail($id);
        $count->forceDelete();

        return response()->json(['message' => 'Vehicle stock count permanently deleted']);
    }
}
