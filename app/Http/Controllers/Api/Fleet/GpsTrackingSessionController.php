<?php
/**
 * =====================================================================
 * متحكم (Controller): GpsTrackingSessionController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Gps Tracking Session
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Gps Tracking Session" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{GpsTrackingSession};
use App\Support\ValidationRules;

class GpsTrackingSessionController extends Controller
{
    /**
     * عرض قائمة سجلات (Gps Tracking Session) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = GpsTrackingSession::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('start_time', 'like', "%{$s}%")
                  ->orWhere('end_time', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Gps Tracking Session) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('gps_tracking_session', 'create'));
        $gpsTrackingSession = GpsTrackingSession::create($data);
        return response()->json($gpsTrackingSession, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Gps Tracking Session) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return GpsTrackingSession::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Gps Tracking Session) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $gpsTrackingSession = GpsTrackingSession::findOrFail($id);
        $data = $request->validate(ValidationRules::for('gps_tracking_session', 'update', $gpsTrackingSession));
        $gpsTrackingSession->update($data);
        return $gpsTrackingSession;
    }

    /**
     * حذف سجل من (Gps Tracking Session) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $gpsTrackingSession = GpsTrackingSession::findOrFail($id);
        $gpsTrackingSession->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Gps Tracking Session) وإعادته للعمل.
     */
    public function restore($id)
    {
        $gpsTrackingSession = GpsTrackingSession::withTrashed()->findOrFail($id);
        $gpsTrackingSession->restore();
        return $gpsTrackingSession;
    }

    /**
     * حذف نهائي للسجل من (Gps Tracking Session) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $gpsTrackingSession = GpsTrackingSession::withTrashed()->findOrFail($id);
        $gpsTrackingSession->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
