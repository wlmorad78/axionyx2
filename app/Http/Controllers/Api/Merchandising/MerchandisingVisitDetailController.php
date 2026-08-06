<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingVisitDetail;
use Illuminate\Http\Request;

class MerchandisingVisitDetailController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingVisitDetail::with(['visit', 'checklist']);

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('score', 'like', "%{$s}%");
            });
        }

        if ($request->filled('merchandising_visit_id')) $query->where('merchandising_visit_id', $request->merchandising_visit_id);
        if ($request->filled('checklist_id')) $query->where('checklist_id', $request->checklist_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'merchandising_visit_id' => 'required|exists:merchandising_visits,id',
            'checklist_id' => 'required|exists:merchandising_checklists,id',
            'score' => 'numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $detail = MerchandisingVisitDetail::create($data);
        return response()->json($detail, 201);
    }

    public function show($id)
    {
        return MerchandisingVisitDetail::with(['visit', 'checklist'])->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $detail = MerchandisingVisitDetail::findOrFail($id);

        $data = $request->validate([
            'merchandising_visit_id' => 'sometimes|required|exists:merchandising_visits,id',
            'checklist_id' => 'sometimes|required|exists:merchandising_checklists,id',
            'score' => 'numeric|min:0',
            'remarks' => 'nullable|string',
        ]);

        $detail->update($data);
        return $detail;
    }

    public function destroy($id)
    {
        $detail = MerchandisingVisitDetail::findOrFail($id);
        $detail->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $detail = MerchandisingVisitDetail::withTrashed()->findOrFail($id);
        $detail->restore();
        return $detail;
    }

    public function forceDelete($id)
    {
        $detail = MerchandisingVisitDetail::withTrashed()->findOrFail($id);
        $detail->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
