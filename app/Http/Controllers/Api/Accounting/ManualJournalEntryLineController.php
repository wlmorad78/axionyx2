<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ManualJournalEntryLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ManualJournalEntryLineController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('manual_journal_entry_line', 'store'));
        return response()->json(ManualJournalEntryLine::create($data), 201);
    }

    public function show(ManualJournalEntryLine $manualJournalEntryLine)
    {
        return $manualJournalEntryLine->load(['manualJournalEntry', 'account']);
    }

    public function update(Request $request, ManualJournalEntryLine $manualJournalEntryLine)
    {
        $data = $request->validate(ValidationRules::for('manual_journal_entry_line', 'update', $manualJournalEntryLine));
        $manualJournalEntryLine->update($data);
        return response()->json($manualJournalEntryLine);
    }

    public function destroy(ManualJournalEntryLine $manualJournalEntryLine)
    {
        $manualJournalEntryLine->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = ManualJournalEntryLine::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        ManualJournalEntryLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('manual_journal_entry_line', 'store');
    }
}
