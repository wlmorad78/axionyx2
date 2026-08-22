<?php
/**
 * =====================================================================
 * متحكم (Controller): ManualJournalEntryLineController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Manual Journal Entry Line
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Manual Journal Entry Line" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ManualJournalEntryLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ManualJournalEntryLineController extends Controller
{
    /**
     * عرض قائمة سجلات (Manual Journal Entry Line) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ManualJournalEntryLine::with($with);
        if ($request->manual_journal_entry_id) $query->where('manual_journal_entry_id', $request->manual_journal_entry_id);
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
     * إنشاء سجل جديد لـ (Manual Journal Entry Line) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('manual_journal_entry_line', 'store'));
        return response()->json(ManualJournalEntryLine::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Manual Journal Entry Line) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(ManualJournalEntryLine $manualJournalEntryLine)
    {
        return $manualJournalEntryLine->load(['manualJournalEntry', 'account']);
    }

    /**
     * تحديث بيانات سجل موجود من (Manual Journal Entry Line) بناءً على المعرّف.
     */
    public function update(Request $request, ManualJournalEntryLine $manualJournalEntryLine)
    {
        $data = $request->validate(ValidationRules::for('manual_journal_entry_line', 'update', $manualJournalEntryLine));
        $manualJournalEntryLine->update($data);
        return response()->json($manualJournalEntryLine);
    }

    /**
     * حذف سجل من (Manual Journal Entry Line) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(ManualJournalEntryLine $manualJournalEntryLine)
    {
        $manualJournalEntryLine->delete();
        return response()->json(null, 204);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Manual Journal Entry Line) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = ManualJournalEntryLine::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Manual Journal Entry Line) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        ManualJournalEntryLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Manual Journal Entry Line).
     */
    public function schema()
    {
        return ValidationRules::for('manual_journal_entry_line', 'store');
    }
}
