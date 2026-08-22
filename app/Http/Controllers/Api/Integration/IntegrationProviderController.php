<?php
/**
 * =====================================================================
 * متحكم (Controller): IntegrationProviderController
 * الوحدة (Module): التكامل والربط مع الأنظمة الخارجية (Integration)
 * المورد (Resource): Integration Provider
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Integration Provider" ضمن وحدة "التكامل والربط مع الأنظمة الخارجية".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\IntegrationProvider;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class IntegrationProviderController extends Controller
{
    /**
     * عرض قائمة سجلات (Integration Provider) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = IntegrationProvider::query()->with('accounts');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('provider_code', 'like', "%{$s}%")
                    ->orWhere('provider_name', 'like', "%{$s}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Integration Provider) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('integration_provider', 'create'));
        return response()->json(IntegrationProvider::create($data), 201);
    }

    public function show($id) { return IntegrationProvider::with('accounts')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = IntegrationProvider::findOrFail($id);
        $data = $request->validate(ValidationRules::for('integration_provider', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { IntegrationProvider::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }

    public function restore($id) { $m = IntegrationProvider::withTrashed()->findOrFail($id); $m->restore(); return $m; }

    public function forceDelete($id) { IntegrationProvider::withTrashed()->findOrFail($id)->forceDelete(); return response()->json(['message' => 'Permanently deleted']); }
}
