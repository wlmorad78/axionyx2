<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingTask;
use Illuminate\Http\Request;

class MerchandisingTaskController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingTask::with('assignments');

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required',
            'task_name' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $item = MerchandisingTask::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = MerchandisingTask::with('assignments')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = MerchandisingTask::findOrFail($id);

        $validated = $request->validate([
            'company_id' => 'required',
            'task_name' => 'required',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = MerchandisingTask::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = MerchandisingTask::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = MerchandisingTask::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
