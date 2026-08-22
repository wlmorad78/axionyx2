<?php
/**
 * =====================================================================
 * متحكم (Controller): DisplayLocationController
 * الوحدة (Module): المخزون والمستودعات (Inventory)
 * المورد (Resource): Display Location
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Display Location" ضمن وحدة "المخزون والمستودعات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Inventory;

use App\Http\Controllers\Controller;
use App\Models\DisplayLocation;
use Illuminate\Http\Request;

class DisplayLocationController extends Controller
{
    /**
     * عرض قائمة سجلات (Display Location) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DisplayLocation::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('location_code')) {
            $query->where('location_code', $request->location_code);
        }

        $locations = $query->paginate($request->get('per_page', 15));

        return response()->json($locations);
    }

    /**
     * إنشاء سجل جديد لـ (Display Location) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'location_code' => 'required|string|max:50|unique:display_locations,location_code',
            'location_name' => 'required',
            'description' => 'nullable',
        ]);

        $location = DisplayLocation::create($validated);

        return response()->json($location, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Display Location) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $location = DisplayLocation::findOrFail($id);

        return response()->json($location);
    }

    /**
     * تحديث بيانات سجل موجود من (Display Location) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $location = DisplayLocation::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'location_code' => 'required|string|max:50|unique:display_locations,location_code,' . $id,
            'location_name' => 'required',
            'description' => 'nullable',
        ]);

        $location->update($validated);

        return response()->json($location);
    }

    /**
     * حذف سجل من (Display Location) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $location = DisplayLocation::findOrFail($id);
        $location->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Display Location) وإعادته للعمل.
     */
    public function restore($id)
    {
        $location = DisplayLocation::onlyTrashed()->findOrFail($id);
        $location->restore();

        return response()->json($location);
    }

    /**
     * حذف نهائي للسجل من (Display Location) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $location = DisplayLocation::onlyTrashed()->findOrFail($id);
        $location->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
