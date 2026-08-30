<?php
/**
 * =====================================================================
 * متحكم (Controller): JournalEntryTypeController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Journal Entry Type
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Journal Entry Type" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryTypeController extends Controller
{
    /**
     * عرض قائمة سجلات (Journal Entry Type) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JournalEntryType::with($with);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%$s%")->orWhere('code', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Journal Entry Type) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_type', 'store'));
        return response()->json(JournalEntryType::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Journal Entry Type) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(JournalEntryType $journalEntryType)
    {
        return $journalEntryType->load(['journalEntries']);
    }

    /**
     * تحديث بيانات سجل موجود من (Journal Entry Type) بناءً على المعرّف.
     */
    public function update(Request $request, JournalEntryType $journalEntryType)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_type', 'update', $journalEntryType));
        $journalEntryType->update($data);
        return response()->json($journalEntryType);
    }

    /**
     * حذف سجل من (Journal Entry Type) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(JournalEntryType $journalEntryType)
    {
        $journalEntryType->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Journal Entry Type) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = JournalEntryType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Journal Entry Type) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        JournalEntryType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Journal Entry Type).
     */
    public function schema()
    {
        return ValidationRules::for('journal_entry_type', 'store');
    }
}
