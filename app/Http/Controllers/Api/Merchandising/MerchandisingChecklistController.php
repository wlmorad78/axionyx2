<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingChecklist;
use Illuminate\Http\Request;

class MerchandisingChecklistController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingChecklist::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('check_code', 'like', "%{$s}%")
                    ->orWhere('check_name', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_active')) $query->where('is_active', $request->is_active);
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'check_code' => 'required|string|max:255|unique:merchandising_checklists,check_code',
            'check_name' => 'required|string|max:255',
            'max_score' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $checklist = MerchandisingChecklist::create($data);
        return response()->json($checklist, 201);
    }

    public function show($id)
    {
        return MerchandisingChecklist::with('visitDetails')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $checklist = MerchandisingChecklist::findOrFail($id);

        $data = $request->validate([
            'company_id' => 'sometimes|required|exists:companies,id',
            'check_code' => 'sometimes|required|string|max:255|unique:merchandising_checklists,check_code,' . $id,
            'check_name' => 'sometimes|required|string|max:255',
            'max_score' => 'integer|min:1',
            'is_active' => 'boolean',
        ]);

        $checklist->update($data);
        return $checklist;
    }

    public function destroy($id)
    {
        $checklist = MerchandisingChecklist::findOrFail($id);
        $checklist->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $checklist = MerchandisingChecklist::withTrashed()->findOrFail($id);
        $checklist->restore();
        return $checklist;
    }

    public function forceDelete($id)
    {
        $checklist = MerchandisingChecklist::withTrashed()->findOrFail($id);
        $checklist->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
