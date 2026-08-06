<?php

namespace App\Http\Controllers\Api\Fleet;

use App\Http\Controllers\Controller;
use App\Models\VehicleDocument;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class VehicleDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleDocument::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('document_number', 'like', "%{$s}%")
                    ->orWhere('document_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('vehicle_id')) $query->where('vehicle_id', $request->vehicle_id);
        if ($request->filled('document_type')) $query->where('document_type', $request->document_type);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('vehicle_document', 'create'));
        $item = VehicleDocument::create($data);
        return response()->json($item, 201);
    }

    public function show($id)
    {
        return VehicleDocument::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $item = VehicleDocument::findOrFail($id);
        $data = $request->validate(ValidationRules::for('vehicle_document', 'update', $item));
        $item->update($data);
        return $item;
    }

    public function destroy($id)
    {
        $item = VehicleDocument::findOrFail($id);
        $item->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $item = VehicleDocument::withTrashed()->findOrFail($id);
        $item->restore();
        return $item;
    }

    public function forceDelete($id)
    {
        $item = VehicleDocument::withTrashed()->findOrFail($id);
        $item->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
