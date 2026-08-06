<?php
namespace App\Http\Controllers\Api\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Accounting\JournalEntryType;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class JournalEntryTypeController extends Controller
{
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

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_type', 'store'));
        return response()->json(JournalEntryType::create($data), 201);
    }

    public function show(JournalEntryType $journalEntryType)
    {
        return $journalEntryType->load(['journalEntries']);
    }

    public function update(Request $request, JournalEntryType $journalEntryType)
    {
        $data = $request->validate(ValidationRules::for('journal_entry_type', 'update', $journalEntryType));
        $journalEntryType->update($data);
        return response()->json($journalEntryType);
    }

    public function destroy(JournalEntryType $journalEntryType)
    {
        $journalEntryType->delete();
        return response()->json(null, 204);
    }

    public function restore(int $id)
    {
        $m = JournalEntryType::onlyTrashed()->findOrFail($id);
        $m->restore();
        return response()->json($m);
    }

    public function forceDelete(int $id)
    {
        JournalEntryType::onlyTrashed()->findOrFail($id)->forceDelete();
        return response()->json(null, 204);
    }

    public function schema()
    {
        return ValidationRules::for('journal_entry_type', 'store');
    }
}
