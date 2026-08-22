<?php
/**
 * =====================================================================
 * متحكم (Controller): LoginLogController
 * الوحدة (Module): المصادقة وتسجيل الدخول (Auth)
 * المورد (Resource): Login Log
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Login Log" ضمن وحدة "المصادقة وتسجيل الدخول".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LoginLogController extends Controller
{
    /**
     * عرض قائمة سجلات (Login Log) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = LoginLog::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('ip_address', 'like', "%{$s}%")
                    ->orWhere('device_name', 'like', "%{$s}%")
                    ->orWhere('browser', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Login Log) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('login_log', 'create'));
        $loginLog = LoginLog::create($data);
        return response()->json($loginLog, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Login Log) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return LoginLog::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Login Log) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $loginLog = LoginLog::findOrFail($id);
        $data = $request->validate(ValidationRules::for('login_log', 'update', $loginLog));
        $loginLog->update($data);
        return $loginLog;
    }

    /**
     * حذف سجل من (Login Log) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $loginLog = LoginLog::findOrFail($id);
        $loginLog->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Login Log) وإعادته للعمل.
     */
    public function restore($id)
    {
        $loginLog = LoginLog::withTrashed()->findOrFail($id);
        $loginLog->restore();
        return $loginLog;
    }

    /**
     * حذف نهائي للسجل من (Login Log) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $loginLog = LoginLog::withTrashed()->findOrFail($id);
        $loginLog->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
