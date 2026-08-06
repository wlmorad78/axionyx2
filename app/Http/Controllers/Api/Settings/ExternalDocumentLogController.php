<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Controller;
use App\Models\Settings\ExternalDocumentLog;
use Illuminate\Http\Request;

class ExternalDocumentLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ExternalDocumentLog::query()->with('document');
        if ($request->filled('external_document_id')) $query->where('external_document_id', $request->external_document_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return ExternalDocumentLog::with('document')->findOrFail($id); }
}
