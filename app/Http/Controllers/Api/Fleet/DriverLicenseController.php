<?php
/**
 * =====================================================================
 * متحكم (Controller): DriverLicenseController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Driver License
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Driver License" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\{DriverLicense};
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverLicenseController extends Controller
{
    /**
     * عرض قائمة سجلات (Driver License) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = DriverLicense::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('license_number', 'like', "%{$s}%")
                  ->orWhere('license_type', 'like', "%{$s}%");
            });
        }
        if ($request->filled('driver_id')) $query->where('driver_id', $request->driver_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }
    /**
     * إنشاء سجل جديد لـ (Driver License) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver_license', 'create'));
        $item = DriverLicense::create($data);
        return response()->json($item, 201);
    }
    public function show($id) { return DriverLicense::findOrFail($id); }
    public function update(Request $request, $id)
    {
        $item = DriverLicense::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver_license', 'update', $item));
        $item->update($data);
        return $item;
    }
    /**
     * حذف سجل من (Driver License) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = DriverLicense::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }
    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Driver License) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = DriverLicense::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }
    /**
     * حذف نهائي للسجل من (Driver License) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = DriverLicense::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
