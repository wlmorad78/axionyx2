<?php
/**
 * =====================================================================
 * متحكم (Controller): DriverController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Driver
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Driver" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    /**
     * عرض قائمة سجلات (Driver) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('license_no', 'like', "%{$s}%")
                    ->orWhere('mobile', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Driver) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('driver', 'create'));
        $driver = Driver::create($data);
        return response()->json($driver, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Driver) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return Driver::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Driver) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $driver = Driver::findOrFail($id);
        $data = $request->validate(ValidationRules::for('driver', 'update', $driver));
        $driver->update($data);
        return $driver;
    }

    /**
     * حذف سجل من (Driver) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $driver = Driver::findOrFail($id);
        $driver->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Driver) وإعادته للعمل.
     */
    public function restore($id)
    {
        $driver = Driver::withTrashed()->findOrFail($id);
        $driver->restore();
        return $driver;
    }

    /**
     * حذف نهائي للسجل من (Driver) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $driver = Driver::withTrashed()->findOrFail($id);
        $driver->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
