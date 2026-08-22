<?php
/**
 * =====================================================================
 * متحكم (Controller): ApiTokenController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Api Token
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Api Token" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    /**
     * عرض قائمة سجلات (Api Token) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = ApiToken::query()->with('client');
        if ($request->filled('api_client_id')) $query->where('api_client_id', $request->api_client_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Api Token) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('api_token', 'create'));
        return response()->json(ApiToken::create($data), 201);
    }

    public function show($id) { return ApiToken::with('client')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ApiToken::findOrFail($id);
        $data = $request->validate(ValidationRules::for('api_token', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ApiToken::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
