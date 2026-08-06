<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\DocumentCategory;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DocumentCategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = DocumentCategory::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('document_category', 'create'));
        $documentCategory = DocumentCategory::create($data);
        return response()->json($documentCategory, 201);
    }

    public function show($id)
    {
        return DocumentCategory::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $documentCategory = DocumentCategory::findOrFail($id);
        $data = $request->validate(ValidationRules::for('document_category', 'update', $documentCategory));
        $documentCategory->update($data);
        return $documentCategory;
    }

    public function destroy($id)
    {
        $documentCategory = DocumentCategory::findOrFail($id);
        $documentCategory->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $documentCategory = DocumentCategory::withTrashed()->findOrFail($id);
        $documentCategory->restore();
        return $documentCategory;
    }

    public function forceDelete($id)
    {
        $documentCategory = DocumentCategory::withTrashed()->findOrFail($id);
        $documentCategory->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
