<?php
/**
 * =====================================================================
 * متحكم (Controller): JournalEntryLineController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Journal Entry Line
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Journal Entry Line" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryLineController extends Controller
{
    /**
     * عرض قائمة سجلات (Journal Entry Line) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JournalEntryLine::with($with);
        if ($request->journal_entry_id) $query->where('journal_entry_id', $request->journal_entry_id);
        if ($request->account_id) $query->where('account_id', $request->account_id);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Journal Entry Line) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_line', 'store'));
        return response()->json(JournalEntryLine::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Journal Entry Line) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(JournalEntryLine $journalEntryLine)
    {
        return $journalEntryLine->load(['journalEntry', 'account']);
    }

    /**
     * تحديث بيانات سجل موجود من (Journal Entry Line) بناءً على المعرّف.
     */
    public function update(Request $request, JournalEntryLine $journalEntryLine)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_line', 'update', $journalEntryLine));
        $journalEntryLine->update($data);
        return response()->json($journalEntryLine);
    }

    /**
     * حذف سجل من (Journal Entry Line) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(JournalEntryLine $journalEntryLine)
    {
        $journalEntryLine->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Journal Entry Line) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = JournalEntryLine::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Journal Entry Line) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        JournalEntryLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Journal Entry Line).
     */
    public function schema()
    {
        return ValidationRules::for('journal_entry_line', 'store');
    }
}
