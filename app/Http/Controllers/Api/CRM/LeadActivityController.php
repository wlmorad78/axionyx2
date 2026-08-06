<?php

namespace App\Http\Controllers\Api\CRM;

use App\Http\Controllers\Controller;
use App\Models\LeadActivity;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class LeadActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = LeadActivity::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('activity_type', 'like', "%{$s}%")
                    ->orWhere('notes', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('lead_activity', 'create'));
        $leadActivity = LeadActivity::create($data);
        return response()->json($leadActivity, 201);
    }

    public function show($id)
    {
        return LeadActivity::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $leadActivity = LeadActivity::findOrFail($id);
        $data = $request->validate(ValidationRules::for('lead_activity', 'update', $leadActivity));
        $leadActivity->update($data);
        return $leadActivity;
    }

    public function destroy($id)
    {
        $leadActivity = LeadActivity::findOrFail($id);
        $leadActivity->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $leadActivity = LeadActivity::withTrashed()->findOrFail($id);
        $leadActivity->restore();
        return $leadActivity;
    }

    public function forceDelete($id)
    {
        $leadActivity = LeadActivity::withTrashed()->findOrFail($id);
        $leadActivity->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
