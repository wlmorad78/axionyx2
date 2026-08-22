<?php
/**
 * =====================================================================
 * متحكم (Controller): MobileDeviceController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Mobile Device
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Mobile Device" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MobileDevice};
use App\Support\ValidationRules;

class MobileDeviceController extends Controller
{
    /**
     * عرض قائمة سجلات (Mobile Device) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MobileDevice::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('device_uuid', 'like', "%{$s}%")
                  ->orWhere('device_name', 'like', "%{$s}%")
                  ->orWhere('status', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Mobile Device) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('mobile_device', 'create'));
        $mobileDevice = MobileDevice::create($data);
        return response()->json($mobileDevice, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Mobile Device) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return MobileDevice::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Mobile Device) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $mobileDevice = MobileDevice::findOrFail($id);
        $data = $request->validate(ValidationRules::for('mobile_device', 'update', $mobileDevice));
        $mobileDevice->update($data);
        return $mobileDevice;
    }

    /**
     * حذف سجل من (Mobile Device) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $mobileDevice = MobileDevice::findOrFail($id);
        $mobileDevice->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Mobile Device) وإعادته للعمل.
     */
    public function restore($id)
    {
        $mobileDevice = MobileDevice::withTrashed()->findOrFail($id);
        $mobileDevice->restore();
        return $mobileDevice;
    }

    /**
     * حذف نهائي للسجل من (Mobile Device) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $mobileDevice = MobileDevice::withTrashed()->findOrFail($id);
        $mobileDevice->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
