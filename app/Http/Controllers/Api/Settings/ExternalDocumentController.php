<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ExternalDocument;
use App\Support\ValidationRules;
use Illuminate\Http\Request;

class ExternalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $query = ExternalDocument::query()->with('provider', 'logs');
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('external_document_no', 'like', "%{$s}%");
            });
        }
        if ($request->filled('company_id')) $query->where('company_id', $request->company_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('external_document', 'create'));
        return response()->json(ExternalDocument::create($data), 201);
    }

    public function show($id) { return ExternalDocument::with('provider', 'logs')->findOrFail($id); }

    public function update(Request $request, $id)
    {
        $model = ExternalDocument::findOrFail($id);
        $data = $request->validate(ValidationRules::for('external_document', 'update', $model));
        $model->update($data);
        return $model;
    }

    public function destroy($id) { ExternalDocument::findOrFail($id)->delete(); return response()->json(['message' => 'Deleted']); }
}
