<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ManualJournalEntry;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ManualJournalEntryController extends Controller
{
    public function index(Request $request)
    {
        $with = $request->with ? explode(',', $request->with) : [];
        $query = ManualJournalEntry::with($with);
        if ($request->company_id) $query->where('company_id', $request->company_id);
        if ($request->branch_id) $query->where('branch_id', $request->branch_id);
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
        $data = $request->validate(ValidationRules::for('manual_journal_entry', 'store'));
        if (empty($data['entry_no'])) {
            $data['entry_no'] = self::generateNextCode();
        }
        return response()->json(ManualJournalEntry::create($data), 201);
    }

    public function show(ManualJournalEntry $manualJournalEntry)
    {
        return $manualJournalEntry->load([
            'company', 'branch', 'fiscalYear', 'accountingPeriod',
            'createdByEmployee', 'approvedByEmployee',
            'lines.account', 'lines',
        ]);
    }

    public function update(Request $request, ManualJournalEntry $manualJournalEntry)
    {
        $data = $request->validate(ValidationRules::for('manual_journal_entry', 'update', $manualJournalEntry));
        $manualJournalEntry->update($data);
        return response()->json($manualJournalEntry);
    }

    public function destroy(ManualJournalEntry $manualJournalEntry)
    {
        $manualJournalEntry->delete();
        return response()->json(null, 204);
    }

    public function nextCode()
    {
        return response()->json(['entry_no' => self::generateNextCode()]);
    }

    public function restore(int $id)
    {
        $m = ManualJournalEntry::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        ManualJournalEntry::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('manual_journal_entry', 'store');
    }

    private static function generateNextCode(): string
    {
        $last = ManualJournalEntry::orderByDesc('id')->value('entry_no');
        if (!$last) return 'MJE-00001';
        $num = (int) substr($last, 4) + 1;
        return 'MJE-' . str_pad($num, 5, '0', STR_PAD_LEFT);
    }
}
