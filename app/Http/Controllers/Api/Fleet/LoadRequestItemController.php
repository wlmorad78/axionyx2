<?php
/**
 * =====================================================================
 * متحكم (Controller): LoadRequestItemController
 * الوحدة (Module): إدارة أسطول المركبات (Fleet)
 * المورد (Resource): Load Request Item
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Load Request Item" ضمن وحدة "إدارة أسطول المركبات".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\LoadRequestItem;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LoadRequestItemController extends Controller
{
    /**
     * عرض قائمة سجلات (Load Request Item) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = LoadRequestItem::with($with);
        if ($request->load_request_id) $query->where('load_request_id', $request->load_request_id);
        if ($request->item_id) $query->where('item_id', $request->item_id);
        if ($request->search) {
            $s = $request->search;
            $query->whereHas('item', function ($q) use ($s) {
                $q->where('name_ar', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Load Request Item) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('load_request_item', 'store'));
        $item = LoadRequestItem::create($data);
        return response()->json($item, 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Load Request Item) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(LoadRequestItem $loadRequestItem)
    {
        return $loadRequestItem->load(['loadRequest', 'item', 'unit']);
    }

    /**
     * تحديث بيانات سجل موجود من (Load Request Item) بناءً على المعرّف.
     */
    public function update(Request $request, LoadRequestItem $loadRequestItem)
    {
        $data = $request->validate(ValidationRules::for('load_request_item', 'update', $loadRequestItem));
        $loadRequestItem->update($data);
        return response()->json($loadRequestItem);
    }

    /**
     * حذف سجل من (Load Request Item) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(LoadRequestItem $loadRequestItem)
    {
        $loadRequestItem->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Load Request Item) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = LoadRequestItem::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Load Request Item) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        LoadRequestItem::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Load Request Item).
     */
    public function schema()
    {
        return ValidationRules::for('load_request_item', 'store');
    }
}
