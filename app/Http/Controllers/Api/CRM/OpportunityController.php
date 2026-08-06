<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\CRM\Opportunity;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class OpportunityController extends Controller
{
    public function index(Request $request)
    {
        $query = Opportunity::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('opportunity_name', 'like', "%{$s}%")
                    ->orWhere('stage', 'like', "%{$s}%")
                    ->orWhere('status', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('opportunity', 'create'));
        $opportunity = Opportunity::create($data);
        return response()->json($opportunity, 201);
    }

    public function show($id)
    {
        return Opportunity::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $data = $request->validate(ValidationRules::for('opportunity', 'update', $opportunity));
        $opportunity->update($data);
        return $opportunity;
    }

    public function destroy($id)
    {
        $opportunity = Opportunity::findOrFail($id);
        $opportunity->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $opportunity = Opportunity::withTrashed()->findOrFail($id);
        $opportunity->restore();
        return $opportunity;
    }

    public function forceDelete($id)
    {
        $opportunity = Opportunity::withTrashed()->findOrFail($id);
        $opportunity->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
