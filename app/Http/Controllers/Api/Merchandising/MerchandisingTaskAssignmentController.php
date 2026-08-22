<?php
/**
 * =====================================================================
 * متحكم (Controller): MerchandisingTaskAssignmentController
 * الوحدة (Module): الترتيب والتنسيق التجاري (Merchandising) (Merchandising)
 * المورد (Resource): Merchandising Task Assignment
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Merchandising Task Assignment" ضمن وحدة "الترتيب والتنسيق التجاري (Merchandising)".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingTaskAssignment;
use Illuminate\Http\Request;

class MerchandisingTaskAssignmentController extends Controller
{
    /**
     * عرض قائمة سجلات (Merchandising Task Assignment) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = MerchandisingTaskAssignment::with(['customer', 'salesRep', 'task']);

        if ($request->filled('merchandising_task_id')) {
            $query->where('merchandising_task_id', $request->merchandising_task_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('sales_rep_id')) {
            $query->where('sales_rep_id', $request->sales_rep_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    /**
     * إنشاء سجل جديد لـ (Merchandising Task Assignment) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_task_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'assigned_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:PENDING,COMPLETED,OVERDUE',
        ]);

        $item = MerchandisingTaskAssignment::create($validated);

        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Merchandising Task Assignment) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        $item = MerchandisingTaskAssignment::with(['customer', 'salesRep', 'task'])->findOrFail($id);

        return response()->json($item);
    }

    /**
     * تحديث بيانات سجل موجود من (Merchandising Task Assignment) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = MerchandisingTaskAssignment::findOrFail($id);

        $validated = $request->validate([
            'merchandising_task_id' => 'required',
            'customer_id' => 'required',
            'sales_rep_id' => 'nullable',
            'assigned_date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|in:PENDING,COMPLETED,OVERDUE',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    /**
     * حذف سجل من (Merchandising Task Assignment) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = MerchandisingTaskAssignment::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Merchandising Task Assignment) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = MerchandisingTaskAssignment::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    /**
     * حذف نهائي للسجل من (Merchandising Task Assignment) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = MerchandisingTaskAssignment::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
