<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\JournalEntry;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry', 'store'));
        if (empty($data['entry_no'])) {
            $data['entry_no'] = self::generateNextCode();
        }
        return response()->json(JournalEntry::create($data), 201);
    }

    public function show(JournalEntry $journalEntry)
    {
        return $journalEntry->load([
            'journalEntryType', 'company', 'branch', 'fiscalYear', 'accountingPeriod',
            'createdByEmployee', 'approvedByEmployee',
            'lines.account', 'lines',
        ]);
    }

    public function update(Request $request, JournalEntry $journalEntry)
    {
        $data = $request->validate(ValidationRules::for('journal_entry', 'update', $journalEntry));
        $journalEntry->update($data);
        return response()->json($journalEntry);
    }

    public function destroy(JournalEntry $journalEntry)
    {
        $journalEntry->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['entry_no' => self::generateNextCode()]);
    }

    public function restore(int $id)
    {
        $m = JournalEntry::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        JournalEntry::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('journal_entry', 'store');
    }

    private static function generateNextCode(): string
    {
        $last = JournalEntry::orderByDesc('id')->value('entry_no');
        if (!$last) return 'JE-00001';
        $num = (int) substr($last, 3) + 1;
        return 'JE-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
