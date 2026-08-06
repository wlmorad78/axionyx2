<?php

namespace App\Http\Controllers\Api\Notifications;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\{MessageTemplate};
use App\Support\ValidationRules;

class MessageTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = MessageTemplate::query();

        if ($request->branch_id) {
            $query->where('branch_id', $request->branch_id);
        }
        if ($s = $request->input('search')) {
            $query->where(function ($q) use ($s) {
                $q->where('template_code', 'like', "%{$s}%")
                  ->orWhere('template_name', 'like', "%{$s}%")
                  ->orWhere('channel', 'like', "%{$s}%");
            });
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        $perPage = min((int) $request->input('per_page', 15), 100);
        return $query->orderByDesc('id')->paginate($perPage);
    }

    public function store(Request $request)
    {
        $data = $request->validate(ValidationRules::for('message_template', 'create'));
        $messageTemplate = MessageTemplate::create($data);
        return response()->json($messageTemplate, 201);
    }

    public function show($id)
    {
        return MessageTemplate::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $messageTemplate = MessageTemplate::findOrFail($id);
        $data = $request->validate(ValidationRules::for('message_template', 'update', $messageTemplate));
        $messageTemplate->update($data);
        return $messageTemplate;
    }

    public function destroy($id)
    {
        $messageTemplate = MessageTemplate::findOrFail($id);
        $messageTemplate->delete();
        return response()->json(['message' => 'Deleted']);
    }

    public function restore($id)
    {
        $messageTemplate = MessageTemplate::withTrashed()->findOrFail($id);
        $messageTemplate->restore();
        return $messageTemplate;
    }

    public function forceDelete($id)
    {
        $messageTemplate = MessageTemplate::withTrashed()->findOrFail($id);
        $messageTemplate->forceDelete();
        return response()->json(['message' => 'Permanently deleted']);
    }
}
