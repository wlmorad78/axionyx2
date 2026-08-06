<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\Document;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = Document::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('document_name', 'like', "%{$s}%")
                    ->orWhere('file_path', 'like', "%{$s}%")
                    ->orWhere('reference_type', 'like', "%{$s}%");
            });
        }

        if ($request->filled('status')) $query->where('status', $request->status);

        $perPage = min((int) $request->input('per_page', 15), 100);

        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('document', 'create'));
        $document = Document::create($data);
        return response()->json($document, 201);
    }

    public function show($id)
    {
        return Document::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $document = Document::findOrFail($id);
        $data = $request->validate(ValidationRules::for('document', 'update', $document));
        $document->update($data);
        return $document;
    }

    public function destroy($id)
    {
        $document = Document::findOrFail($id);
        $document->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->restore();
        return $document;
    }

    public function forceDelete($id)
    {
        $document = Document::withTrashed()->findOrFail($id);
        $document->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
