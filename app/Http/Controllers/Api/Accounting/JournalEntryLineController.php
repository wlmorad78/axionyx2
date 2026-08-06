<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\JournalEntryLine;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryLineController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_line', 'store'));
        return response()->json(JournalEntryLine::create($data), 201);
    }

    public function show(JournalEntryLine $journalEntryLine)
    {
        return $journalEntryLine->load(['journalEntry', 'account']);
    }

    public function update(Request $request, JournalEntryLine $journalEntryLine)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_line', 'update', $journalEntryLine));
        $journalEntryLine->update($data);
        return response()->json($journalEntryLine);
    }

    public function destroy(JournalEntryLine $journalEntryLine)
    {
        $journalEntryLine->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = JournalEntryLine::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        JournalEntryLine::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('journal_entry_line', 'store');
    }
}
