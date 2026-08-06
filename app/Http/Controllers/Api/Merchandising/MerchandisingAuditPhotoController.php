<?php

namespace App\Http\Controllers\Api\Merchandising;

use App\Http\Controllers\Controller;
use App\Models\MerchandisingAuditPhoto;
use Illuminate\Http\Request;

class MerchandisingAuditPhotoController extends Controller
{
    public function index(Request $request)
    {
        $query = MerchandisingAuditPhoto::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('merchandising_audit_id')) {
            $query->where('merchandising_audit_id', $request->merchandising_audit_id);
        }

        if ($request->filled('photo_type')) {
            $query->where('photo_type', $request->photo_type);
        }

        $items = $query->paginate($request->get('per_page', 15));

        return response()->json($items);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'photo_type' => 'required|in:STORE,SHELF,REFRIGERATOR,DISPLAY,POSM',
            'file_path' => 'required',
            'taken_at' => 'nullable|date',
        ]);

        $item = MerchandisingAuditPhoto::create($validated);

        return response()->json($item, 201);
    }

    public function show($id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, $id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);

        $validated = $request->validate([
            'merchandising_audit_id' => 'required',
            'photo_type' => 'required|in:STORE,SHELF,REFRIGERATOR,DISPLAY,POSM',
            'file_path' => 'required',
            'taken_at' => 'nullable|date',
        ]);

        $item->update($validated);

        return response()->json($item);
    }

    public function destroy($id)
    {
        $item = MerchandisingAuditPhoto::findOrFail($id);
        $item->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }

    public function restore($id)
    {
        $item = MerchandisingAuditPhoto::withTrashed()->findOrFail($id);
        $item->restore();

        return response()->json(['message' => 'Restored successfully']);
    }

    public function forceDelete($id)
    {
        $item = MerchandisingAuditPhoto::withTrashed()->findOrFail($id);
        $item->forceDelete();

        return response()->json(['message' => 'Permanently deleted']);
    }
}
