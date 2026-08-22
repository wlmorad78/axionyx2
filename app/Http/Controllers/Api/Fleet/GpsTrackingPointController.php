<?php
/**
 * =====================================================================
 * متحكم (Controller): GpsTrackingPointController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Gps Tracking Point
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Gps Tracking Point" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{GpsTrackingPoint};
use App\Support\ValidationRules;

class GpsTrackingPointController extends Controller
{
    /**
     * عرض قائمة سجلات (Gps Tracking Point) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = GpsTrackingPoint::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('latitude', 'like', "%{$s}%")
                  ->orWhere('longitude', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Gps Tracking Point) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('gps_tracking_point', 'create'));
        $gpsTrackingPoint = GpsTrackingPoint::create($data);
        return response()->json($gpsTrackingPoint, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Gps Tracking Point) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return GpsTrackingPoint::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Gps Tracking Point) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::findOrFail($id);
        $data = $request->validate(ValidationRules::for('gps_tracking_point', 'update', $gpsTrackingPoint));
        $gpsTrackingPoint->update($data);
        return $gpsTrackingPoint;
    }

    /**
     * حذف سجل من (Gps Tracking Point) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::findOrFail($id);
        $gpsTrackingPoint->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Gps Tracking Point) وإعادته للعمل.
     */
    public function restore($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::withTrashed()->findOrFail($id);
        $gpsTrackingPoint->restore();
        return $gpsTrackingPoint;
    }

    /**
     * حذف نهائي للسجل من (Gps Tracking Point) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $gpsTrackingPoint = GpsTrackingPoint::withTrashed()->findOrFail($id);
        $gpsTrackingPoint->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
