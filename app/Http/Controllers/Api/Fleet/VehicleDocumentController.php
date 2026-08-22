<?php
/**
 * =====================================================================
 * متحكم (Controller): VehicleDocumentController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Vehicle Document
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Vehicle Document" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDocument;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleDocumentController extends Controller
{
    /**
     * عرض قائمة سجلات (Vehicle Document) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $query = VehicleDocument::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                    ->orWhere('document_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('document_type')) $query->where('document_type', $request->document_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * إنشاء سجل جديد لـ (Vehicle Document) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_document', 'create'));
        $item = VehicleDocument::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Vehicle Document) مع العلاقات (Relations) المرتبطة به.
     */
    public function show($id)
    {
        return VehicleDocument::findOrFail($id);
    }

    /**
     * تحديث بيانات سجل موجود من (Vehicle Document) بناءً على المعرّف.
     */
    public function update(Request $request, $id)
    {
        $item = VehicleDocument::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_document', 'update', $item));
        $item->update($data);
        return $item;
    }

    /**
     * حذف سجل من (Vehicle Document) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy($id)
    {
        $item = VehicleDocument::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Vehicle Document) وإعادته للعمل.
     */
    public function restore($id)
    {
        $item = VehicleDocument::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    /**
     * حذف نهائي للسجل من (Vehicle Document) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete($id)
    {
        $item = VehicleDocument::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
