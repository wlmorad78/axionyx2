<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleLoadController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Load
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Load" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleLoad;
use Illuminate\Http\Request;

class VehicleLoadController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Load) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleLoad::with(['items', 'vehicle', 'loadRequest']);

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }
        if ($request->filled('vehicle_id')) {
            $query->where('vehicle_id', $request->vehicle_id);
        }
        if ($request->filled('load_date_from')) {
            $query->where('load_date', '>=', $request->load_date_from);
        }
        if ($request->filled('load_date_to')) {
            $query->where('load_date', '<=', $request->load_date_to);
        }

        $loads = $query->paginate($request->get('per_page', 15));

        return response()->json($loads);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Load) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'vehicle_id' => 'required',
            'load_request_id' => 'nullable',
            'issue_order_id' => 'nullable',
            'load_no' => 'required|unique:vehicle_loads,load_no',
            'load_date' => 'required|date',
            'loaded_value' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'created_by' => 'nullable',
        ]);

        $load = VehicleLoad::create($validated);

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Load) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $load = VehicleLoad::with(['items', 'vehicle', 'loadRequest'])->findOrFail($id);

        return response()->json($load);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Load) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $load = VehicleLoad::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'sometimes|required',
            'vehicle_id' => 'sometimes|required',
            'load_request_id' => 'nullable',
            'issue_order_id' => 'nullable',
            'load_no' => 'sometimes|required|unique:vehicle_loads,load_no,' . $id,
            'load_date' => 'sometimes|required|date',
            'loaded_value' => 'nullable|numeric',
            'loaded_qty' => 'nullable|numeric',
            'created_by' => 'nullable',
        ]);

        $load->update($validated);

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']));
    }

    /**
     * حذف سجل من (Vehicle Load) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $load = VehicleLoad::findOrFail($id);
        $load->delete();

        return response()->json(['message' => 'Vehicle load deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Load) وإعادته للعمل.
     */
    public function restore($id)
    {
        $load = VehicleLoad::onlyTrashed()->findOrFail($id);
        $load->restore();

        return response()->json($load->load(['items', 'vehicle', 'loadRequest']));
    }

    /**
     * حذف نهائي للسجل من (Vehicle Load) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $load = VehicleLoad::onlyTrashed()->findOrFail($id);
        $load->forceDelete();

        return response()->json(['message' => 'Vehicle load permanently deleted']);
    }
}
