<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingTaskController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Task
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Task" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingTask;
use Illuminate\Http\Request;

class MerchandisingTaskController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Task) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingTask::with('assignments');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Task) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'task_name' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $item = MerchandisingTask::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Task) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = MerchandisingTask::with('assignments')->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Task) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = MerchandisingTask::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'task_name' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Merchandising Task) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = MerchandisingTask::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Task) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = MerchandisingTask::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Task) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = MerchandisingTask::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
