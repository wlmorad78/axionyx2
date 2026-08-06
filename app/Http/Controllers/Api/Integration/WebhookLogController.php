<?php

namespace App\Http\Controllers\Api\Integration;

use App\Http\Controllers\Controller;
use App\Models\WebhookLog;
use Illuminate\Http\Request;

class WebhookLogController extends Controller
{
    public function index(Request $request)
    {
        $query = WebhookLog::query()->with('endpoint');
        if ($request->filled('webhook_endpoint_id')) $query->where('webhook_endpoint_id', $request->webhook_endpoint_id);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function show($id) { return WebhookLog::with('endpoint')->findOrFail($id); }
}
