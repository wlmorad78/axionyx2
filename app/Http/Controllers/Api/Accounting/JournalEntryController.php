<?php
/**
 * =====================================================================
 * متحكم (Controller): JournalEntryController
 * الوحدة (Module): المحاسبة (Accounting)
 * المورد (Resource): Journal Entry
 * ---------------------------------------------------------------------
 * الوصف:
 * هذا المتحكم يُعرّف نقاط النهاية (Endpoints) الخاصة بواجهة النظام
 * لإدارة "Journal Entry" ضمن وحدة "المحاسبة".
 * يوفر العمليات الأساسية (CRUD) بالإضافة إلى أي عمليات مخصصة حسب الحاجة،
 * ويعتمد على نماذج (Models) وقواعد تحقق (Validation Rules) لضمان سلامة البيانات.
 * =====================================================================
 */
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\JournalEntry;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
    /**
     * عرض قائمة سجلات (Journal Entry) مع دعم الفلترة والبحث والصفحات (Pagination).
     */
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = JournalEntry::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
        if ($request->journal_entry_type_id) $query->where('journal_entry_type_id', $request->journal_entry_type_id);
        if ($request->fiscal_year_id) $query->where('fiscal_year_id', $request->fiscal_year_id);
        if ($request->accounting_period_id) $query->where('accounting_period_id', $request->accounting_period_id);
        if ($request->status) $query->where('status', $request->status);
        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('entry_no', 'like', "%$s%")->orWhere('description', 'like', "%$s%");
            });
        }
        if ($request->trashed) $query->onlyTrashed();
        return $query->orderByDesc('id')->paginate($request->per_page ?? 15);
    }

    /**
     * إنشاء سجل جديد لـ (Journal Entry) بعد التحقق من صحة البيانات المدخلة.
     */
    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry', 'store'));
        if (empty($data['entry_no'])) {
            $data['entry_no'] = self::generateNextCode();
        }
        return response()->json(JournalEntry::create($data), 201);
    }

    /**
     * عرض تفاصيل سجل محدد من (Journal Entry) مع العلاقات (Relations) المرتبطة به.
     */
    public function show(JournalEntry $journalEntry)
    {
        return $journalEntry->load([
            'journalEntryType', 'company', 'branch', 'fiscalYear', 'accountingPeriod',
            'createdByEmployee', 'approvedByEmployee',
            'lines.account', 'lines',
        ]);
    }

    /**
     * تحديث بيانات سجل موجود من (Journal Entry) بناءً على المعرّف.
     */
    public function update(Request $request, JournalEntry $journalEntry)
    {
        $data = $request->validate(ValidationRules::for('journal_entry', 'update', $journalEntry));
        $journalEntry->update($data);
        return response()->json($journalEntry);
    }

    /**
     * حذف سجل من (Journal Entry) مع مراعاة قواعد العمل قبل الحذف.
     */
    public function destroy(JournalEntry $journalEntry)
    {
        $journalEntry->delete();
        return response()->json(null, 204);
    }

    /**
     * توليد القيمة التلقائية التالية للكود (Code) الخاص بـ (Journal Entry).
     */
    public function nextCode()
    {
        return response()->json(['entry_no' => self::generateNextCode()]);
    }

    /**
     * استرجاع سجل محذوف (Soft Deleted) من (Journal Entry) وإعادته للعمل.
     */
    public function restore(int $id)
    {
        $m = JournalEntry::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    /**
     * حذف نهائي للسجل من (Journal Entry) من قاعدة البيانات دون إمكانية الاسترجاع.
     */
    public function forceDelete(int $id)
    {
        JournalEntry::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    /**
     * إرجاع قواعد التحقق (Validation Rules) المستخدمة لـ (Journal Entry).
     */
    public function schema()
    {
        return ValidationRules::for('journal_entry', 'store');
    }

    /**
     * دالة معالجة: generateNextCode — تُنفّذ نقطة النهاية (Endpoint) المطلوبة لـ (Journal Entry).
     */
    private static function generateNextCode(): string
    {
        $last = JournalEntry::orderByDesc('id')->value('entry_no');
        if (!$last) return 'JE-00001';
        $num = (int) substr($last, 3) + 1;
        return 'JE-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
